<?php
/**
 * AuthController
 * 
 * Handles Login, Logout, and Session management.
 */
require_once 'models/User.php';
require_once 'models/Setting.php';
require_once 'helpers/RecaptchaHelper.php';
require_once 'helpers/RateLimitHelper.php';

class AuthController extends BaseController
{
    private function clientIp()
    {
        return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    // Default action: Redirect to login
    public function index()
    {
        $this->redirect('auth/login');
    }

    /**
     * Show Login Page
     */
    public function login()
    {
        // If already logged in, go to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('admin/dashboard');
            return;
        }

        // Load the view file: views/admin/login.php
        // We pass 'title' to be used in the HTML <title> tag

        // Fetch Settings for Logo and Name
        require_once 'models/Setting.php';
        $settingModel = new Setting();
        $settings = $settingModel->getAllPairs();

        $this->view('admin/login', [
            'title' => 'Login - EcomCMS',
            'settings' => $settings
        ]);
    }

    /**
     * Process Login Form (POST request)
     */
    public function authenticate()
    {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingModel = new Setting();
            $settings = $settingModel->getMultiple([
                'recaptcha_v3_enabled',
                'recaptcha_v3_site_key',
                'recaptcha_v3_secret_key',
                'recaptcha_v3_min_score',
                'recaptcha_v3_admin_login'
            ]);
            $rateLimitKey = 'admin_login:' . $this->clientIp();

            if (RateLimitHelper::tooManyAttempts($rateLimitKey, 8, 900)) {
                $this->redirect('auth/login?error=too_many_attempts');
                return;
            }
            RateLimitHelper::hit($rateLimitKey, 900);

            if (!empty($_POST['company_name'])) {
                $this->redirect('auth/login?error=security_check_failed');
                return;
            }

            if (RecaptchaHelper::shouldProtectAdminLogin($settings)) {
                $verification = RecaptchaHelper::verifyToken(
                    $settings,
                    (string) ($_POST['g_recaptcha_response'] ?? ''),
                    'admin_login'
                );
                if (empty($verification['ok'])) {
                    $this->redirect('auth/login?error=security_check_failed');
                    return;
                }
            }

            // Get inputs
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            // Validate Logic regarding Empty Fields
            if (empty($username) || empty($password)) {
                // Redirect back with error
                // In a real app we'd use Flash Messages, for now using URL param
                $this->redirect('auth/login?error=empty_fields');
                return;
            }

            // Connect to Model
            $userModel = new User();
            $user = $userModel->login($username, $password);

            if ($user) {
                // SUCCESS: Login verified
                // Store user info in Session
                RateLimitHelper::clear($rateLimitKey);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                // Redirect based on role (Developer vs Shop Owner)
                // Both go to the main dashboard now
                $this->redirect('admin/dashboard');

                /* 
                if ($user['role'] === 'developer') {
                    $this->redirect('admin/dashboard');
                } else {
                    $this->redirect('shop/dashboard');
                }
                */
            } else {
                // FAILURE: Wrong credentials
                $this->redirect('auth/login?error=invalid_credentials');
            }
        } else {
            // If someone tries to visit this URL directly without POST
            $this->redirect('auth/login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
        $this->redirect('auth/login');
    }
}
?>
