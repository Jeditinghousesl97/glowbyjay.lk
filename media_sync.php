<?php
/** Private CRM media bridge. The key is provisioned in the website database by CRM. */
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-CRM-Media-Bridge: verify-20261108-01');

function mediaFail(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') mediaFail(405, 'POST required.');
$name = (string)($_SERVER['HTTP_X_MEDIA_FILENAME'] ?? '');
$action = (string)($_SERVER['HTTP_X_MEDIA_ACTION'] ?? '');
$timestamp = (string)($_SERVER['HTTP_X_MEDIA_TIMESTAMP'] ?? '');
$signature = (string)($_SERVER['HTTP_X_MEDIA_SIGNATURE'] ?? '');
if (!preg_match('/^[a-zA-Z0-9._-]+\.(?:jpe?g|png|gif|webp|avif)$/iD', $name)
    || !in_array($action, ['put', 'delete'], true)
    || !ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300
    || !preg_match('/^[a-f0-9]{64}$/D', $signature)) mediaFail(400, 'Invalid media request.');

$pdo = (new Database())->getConnection();
if (!$pdo) mediaFail(503, 'Database unavailable.');
$keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='crm_media_sync_key' LIMIT 1");
$keyStmt->execute();
$key = (string)$keyStmt->fetchColumn();
if (strlen($key) < 32) mediaFail(403, 'Media synchronization is not configured.');
$body = file_get_contents('php://input');
if ($body === false || strlen($body) > 12 * 1024 * 1024) mediaFail(413, 'Media exceeds size limit.');
$expected = hash_hmac('sha256', $action . "\n" . $name . "\n" . $timestamp . "\n" . hash('sha256', $body), $key);
if (!hash_equals($expected, $signature)) mediaFail(403, 'Invalid signature.');

$directory = __DIR__ . '/assets/uploads';
if (!is_dir($directory)) mediaFail(503, 'Upload directory unavailable.');
$path = $directory . '/' . $name;
if ($action === 'put') {
    $image = @getimagesizefromstring($body);
    if (!$image || !in_array($image['mime'] ?? '', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], true)) mediaFail(415, 'Invalid image.');
    $temp = tempnam($directory, 'crmimg_');
    if (!$temp || file_put_contents($temp, $body) !== strlen($body) || !@rename($temp, $path)) {
        if ($temp) @unlink($temp);
        mediaFail(503, 'Image could not be saved.');
    }
    // tempnam creates a private file; Apache must be able to read the published image.
    @chmod($path, 0644);
} else {
    // Database changes must happen first. Never remove a file still in use.
    foreach (['products' => 'main_image', 'product_images' => 'image_path',
              'categories' => 'image', 'product_variant_stock' => 'image_path'] as $table => $column) {
        $query = "SELECT COUNT(*) FROM {$table} WHERE {$column}=? OR {$column} LIKE ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$name, '%/' . $name]);
        if ((int)$stmt->fetchColumn() > 0) mediaFail(409, 'Image is still referenced.');
    }
    if (is_file($path) && !@unlink($path)) mediaFail(503, 'Image could not be removed.');
}
echo json_encode(['success' => true]);
