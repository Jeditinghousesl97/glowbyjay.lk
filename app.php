<?php
/**
 * Main App Router
 *
 * This handles all non-homepage routes. The homepage now lives in index.php.
 */

if (!function_exists('app_is_https')) {
    function app_is_https()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return false;
    }
}

if (!function_exists('app_session_cookie_params')) {
    function app_session_cookie_params()
    {
        return [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => app_is_https(),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }
}

if (function_exists('ini_set')) {
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
}

session_set_cookie_params(app_session_cookie_params());
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return (string) ($_SESSION['csrf_token'] ?? '');
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input()
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('app_send_security_headers')) {
    function app_send_security_headers()
    {
        header_remove('X-Powered-By');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: accelerometer=(), autoplay=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()");
        header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'");

        if (app_is_https()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

if (!function_exists('app_request_origin_is_trusted')) {
    function app_request_origin_is_trusted()
    {
        $source = '';

        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $source = (string) $_SERVER['HTTP_ORIGIN'];
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $source = (string) $_SERVER['HTTP_REFERER'];
        }

        if ($source === '') {
            return false;
        }

        $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $requestScheme = app_is_https() ? 'https' : 'http';
        $requestPort = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : ($requestScheme === 'https' ? 443 : 80);
        $sourceParts = parse_url($source);

        if (empty($sourceParts['host'])) {
            return false;
        }

        $sourceHost = strtolower((string) $sourceParts['host']);
        $sourceScheme = strtolower((string) ($sourceParts['scheme'] ?? $requestScheme));
        $sourcePort = isset($sourceParts['port']) ? (int) $sourceParts['port'] : ($sourceScheme === 'https' ? 443 : 80);

        return $sourceHost === $requestHost && $sourceScheme === $requestScheme && $sourcePort === $requestPort;
    }
}

if (!function_exists('app_is_json_request')) {
    function app_is_json_request()
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return strpos($contentType, 'application/json') !== false
            || strpos($accept, 'application/json') !== false
            || $requestedWith === 'xmlhttprequest';
    }
}

if (!function_exists('app_request_body')) {
    function app_request_body()
    {
        static $body = null;

        if ($body === null) {
            $body = (string) file_get_contents('php://input');
        }

        return $body;
    }
}

if (!function_exists('app_request_json')) {
    function app_request_json()
    {
        static $json = null;
        static $decoded = false;

        if ($decoded) {
            return $json;
        }

        $decoded = true;
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($contentType, 'application/json') === false) {
            $json = [];
            return $json;
        }

        $parsed = json_decode(app_request_body(), true);
        $json = is_array($parsed) ? $parsed : [];

        return $json;
    }
}

if (!function_exists('app_is_csrf_exempt')) {
    function app_is_csrf_exempt($request)
    {
        $normalized = trim((string) $request, '/');
        return in_array($normalized, ['order/payhereNotify', 'order/kokoResponse'], true);
    }
}

if (!function_exists('app_abort_forbidden')) {
    function app_abort_forbidden($message = 'Forbidden request.')
    {
        http_response_code(403);

        if (app_is_json_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }

        echo $message;
        exit;
    }
}

require_once 'config/db.php';
require_once 'controllers/BaseController.php';
require_once 'models/BaseModel.php';

$request = isset($_GET['url']) ? $_GET['url'] : 'home';
$request = rtrim($request, '/');

app_send_security_headers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !app_is_csrf_exempt($request)) {
    $submittedToken = '';
    if (isset($_POST['_csrf'])) {
        $submittedToken = (string) $_POST['_csrf'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submittedToken = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    } else {
        $jsonInput = app_request_json();
        if (isset($jsonInput['_csrf'])) {
            $submittedToken = (string) $jsonInput['_csrf'];
        }
    }

    $tokenValid = $submittedToken !== '' && hash_equals(csrf_token(), $submittedToken);
    $originTrusted = app_request_origin_is_trusted();

    if (!$originTrusted && !$tokenValid) {
        app_abort_forbidden('Your session security check failed. Please refresh and try again.');
    }
}

$params = explode('/', $request);
$controllerName = isset($params[0]) ? $params[0] : 'home';
$actionName = isset($params[1]) ? $params[1] : 'index';
$controllerClass = ucfirst($controllerName) . 'Controller';
$controllerFile = 'controllers/' . $controllerClass . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerClass();

    if (method_exists($controller, $actionName)) {
        call_user_func_array([$controller, $actionName], array_slice($params, 2));
    } else {
        http_response_code(404);
        require_once 'views/errors/404.php';
    }
} else {
    http_response_code(404);
    require_once 'views/errors/404.php';
}
?>
