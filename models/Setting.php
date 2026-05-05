<?php
/**
 * Setting Model (Key-Value Store)
 */
require_once 'models/BaseModel.php';
require_once 'helpers/SecretHelper.php';

class Setting extends BaseModel
{
    private $sensitiveKeys = [
        'payhere_merchant_secret',
        'smtp_password',
        'sms_api_key',
        'koko_api_key',
        'koko_private_key',
        'koko_callback_secret',
        'cloudflare_r2_access_key_id',
        'cloudflare_r2_secret_access_key',
        'recaptcha_v3_secret_key'
    ];

    // Get value by key
    public function get($key, $default = '')
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$res) {
            return $default;
        }

        return $this->decodeValue($key, $res['setting_value']);
    }

    // Set value (Insert or Update)
    public function set($key, $value)
    {
        $storedValue = $this->encodeValue($key, $value);

        // Check if exists
        $sqlCheck = "SELECT id FROM settings WHERE setting_key = :key";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(':key', $key);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            // Update
            $sql = "UPDATE settings SET setting_value = :val WHERE setting_key = :key";
        } else {
            // Insert
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->bindParam(':val', $storedValue);
        return $stmt->execute();
    }

    // Get multiple keys at once
    public function getMultiple($keys)
    {
        // Not strictly optimized (N queries), but fine for 5-6 settings
        $results = [];
        foreach ($keys as $k) {
            $results[$k] = $this->get($k);
        }
        return $results;
    }

    // Get ALL setting pairs [key => value]
    public function getAllPairs()
    {
        $sql = "SELECT setting_key, setting_value FROM settings";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pairs = [];
        foreach ($rows as $r) {
            $pairs[$r['setting_key']] = $this->decodeValue($r['setting_key'], $r['setting_value']);
        }
        return $pairs;
    }

    private function isSensitiveKey($key)
    {
        return in_array($key, $this->sensitiveKeys, true);
    }

    private function encodeValue($key, $value)
    {
        if (!$this->isSensitiveKey($key)) {
            return $value;
        }

        return SecretHelper::encrypt((string) $value);
    }

    private function decodeValue($key, $value)
    {
        if (!$this->isSensitiveKey($key)) {
            return $value;
        }

        return SecretHelper::decrypt((string) $value);
    }
}
?>
