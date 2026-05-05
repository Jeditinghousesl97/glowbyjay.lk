<?php
/**
 * Settings Controller
 * Handles Gatekeeper, Developer Auth, Shop Configuration, and Global Styles.
 */
require_once 'models/Setting.php';
require_once 'models/User.php';
require_once 'helpers/ImageHelper.php';
require_once 'helpers/CloudflareR2Helper.php';
require_once 'models/DeliverySetting.php';
require_once 'helpers/DeliveryHelper.php';

class SettingsController extends BaseController
{

    private $settingModel;
    private $userModel;
    private $deliverySettingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
        require_once 'models/User.php';
        $this->userModel = new User();
        $this->deliverySettingModel = new DeliverySetting();
    }

    // 1. Gatekeeper / Main Entry
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('settings/edit');
            return;
        }
        $this->redirect('auth/login');
    }

    // 2. Show Login Form
    public function login()
    {
        $this->redirect('auth/login');
    }

    // 3. Process Login
    public function authenticate()
    {
        $this->redirect('auth/login');
    }

    // 4. Show The Form (Restricted)
    public function edit()
    {
        $this->checkAuth();

        // Get all settings
        $keys = [
            'shop_name',
            'shop_slogan',
            'shop_url',
            'shop_logo',
            'shop_favicon',
            'shop_qr',
            'shop_about',
            'currency_symbol',
            'shop_whatsapp',
            'google_analytics_id',
            'meta_pixel_id',
            'recaptcha_v3_enabled',
            'recaptcha_v3_site_key',
            'recaptcha_v3_secret_key',
            'recaptcha_v3_min_score',
            'recaptcha_v3_admin_login',
            'recaptcha_v3_checkout',
            'cloudflare_images_enabled',
            'local_image_optimization_enabled',
            'cloudflare_r2_account_id',
            'cloudflare_r2_bucket',
            'cloudflare_r2_access_key_id',
            'cloudflare_r2_secret_access_key',
            'cloudflare_r2_public_base_url',
            'smtp_host',
            'smtp_port',
            'smtp_encryption',
            'smtp_username',
            'smtp_password',
            'smtp_from_email',
            'smtp_from_name',
            'payhere_enabled',
            'payhere_sandbox',
            'payhere_merchant_id',
            'payhere_merchant_secret',
            'koko_enabled',
            'koko_sandbox',
            'koko_title',
            'koko_description',
            'koko_merchant_id',
            'koko_api_key',
            'koko_handling_fee_percentage',
            'koko_public_key',
            'koko_private_key',
            'koko_callback_secret',
            'sms_enabled',
            'sms_base_url',
            'sms_user_id',
            'sms_api_key',
            'sms_sender_id',
            'sms_owner_enabled',
            'sms_template_order_placed',
            'sms_template_payment_completed',
            'sms_template_payment_cancelled',
            'sms_template_payment_failed',
            'sms_template_payment_received',
            'sms_template_order_completed',
            'sms_template_order_cancelled',
            'sms_template_owner_order_received',
            'email_customer_template_order_placed',
            'email_customer_template_payment_completed',
            'email_customer_template_payment_cancelled',
            'email_customer_template_payment_failed',
            'email_customer_template_payment_received',
            'email_customer_template_order_completed',
            'email_customer_template_order_cancelled',
            'email_owner_template_order_placed',
            'email_owner_template_payment_completed',
            'email_owner_template_payment_cancelled',
            'email_owner_template_payment_failed',
            'email_owner_template_payment_received',
            'email_owner_template_order_completed',
            'email_owner_template_order_cancelled'
        ];
        $settings = $this->settingModel->getMultiple($keys);
        $cloudflareStatus = CloudflareR2Helper::statusSummary();
        $testResult = null;
        if (isset($_SESSION['cloudflare_test_result']) && is_array($_SESSION['cloudflare_test_result'])) {
            $testResult = $_SESSION['cloudflare_test_result'];
            unset($_SESSION['cloudflare_test_result']);
        }

        // Get Shop Owner credentials
        // Use LIMIT 1 as we generally assume single tenant/owner for this setup
        $owner = $this->userModel->getByRole('owner');

        $this->view('admin/settings/form', [
            'title' => 'Shop Settings',
            'settings' => $settings,
            'owner' => $owner,
            'cloudflare_status' => $cloudflareStatus,
            'cloudflare_test_result' => $testResult
        ]);
    }

    // 5. Update Settings (General & Owner)
    public function update()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Handle Logo
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
                $fileName = ImageHelper::storeUploadedFile($_FILES['shop_logo'], 'logo');
                if ($fileName !== '') {
                    // Delete Old Logo
                    $oldUrl = $this->settingModel->get('shop_logo');
                    if (!empty($oldUrl)) {
                        $oldFile = basename($oldUrl); // Extract filename from URL (e.g. /uploads/123.jpg -> 123.jpg)
                        $this->deleteFile($oldFile);
                    }

                    $this->settingModel->set('shop_logo', ImageHelper::storedAssetUrl($fileName, BASE_URL . "assets/uploads/" . $fileName));
                }
            }

            // Handle QR
            if (isset($_FILES['shop_qr']) && $_FILES['shop_qr']['error'] == 0) {
                $fileName = ImageHelper::storeUploadedFile($_FILES['shop_qr'], 'qr');
                if ($fileName !== '') {

                    // Delete Old QR
                    $oldUrl = $this->settingModel->get('shop_qr');
                    if (!empty($oldUrl)) {
                        $oldFile = basename($oldUrl);
                        $this->deleteFile($oldFile);
                    }

                    $this->settingModel->set('shop_qr', ImageHelper::storedAssetUrl($fileName, BASE_URL . "assets/uploads/" . $fileName));
                }
            }


            // Handle Favicon
            if (isset($_FILES['shop_favicon']) && $_FILES['shop_favicon']['error'] == 0) {
                $fileName = ImageHelper::storeUploadedFile($_FILES['shop_favicon'], 'fav');
                if ($fileName !== '') {

                    // Delete Old Favicon
                    $oldUrl = $this->settingModel->get('shop_favicon');
                    if (!empty($oldUrl)) {
                        $oldFile = basename($oldUrl);
                        $this->deleteFile($oldFile);
                    }

                    $this->settingModel->set('shop_favicon', ImageHelper::storedAssetUrl($fileName, BASE_URL . "assets/uploads/" . $fileName));
                }
            }

            // Text Fields
            $textFields = [
                'shop_name',
                'shop_slogan',
                'shop_url',
                'shop_about',
                'currency_symbol',
                'shop_whatsapp',
                'google_analytics_id',
                'meta_pixel_id',
                'recaptcha_v3_site_key',
                'recaptcha_v3_min_score',
                'cloudflare_r2_account_id',
                'cloudflare_r2_bucket',
                'cloudflare_r2_public_base_url',
                'smtp_host',
                'smtp_port',
                'smtp_encryption',
                'smtp_username',
                'smtp_from_email',
                'smtp_from_name',
                'payhere_merchant_id',
                'koko_title',
                'koko_description',
                'koko_merchant_id',
                'koko_handling_fee_percentage',
                'koko_public_key',
                'sms_base_url',
                'sms_user_id',
                'sms_sender_id',
                'sms_template_owner_order_received',
                'sms_template_order_placed',
                'sms_template_payment_completed',
                'sms_template_payment_cancelled',
                'sms_template_payment_failed',
                'sms_template_payment_received',
                'sms_template_order_completed',
                'sms_template_order_cancelled',
                'email_customer_template_order_placed',
                'email_customer_template_payment_completed',
                'email_customer_template_payment_cancelled',
                'email_customer_template_payment_failed',
                'email_customer_template_payment_received',
                'email_customer_template_order_completed',
                'email_customer_template_order_cancelled',
                'email_owner_template_order_placed',
                'email_owner_template_payment_completed',
                'email_owner_template_payment_cancelled',
                'email_owner_template_payment_failed',
                'email_owner_template_payment_received',
                'email_owner_template_order_completed',
                'email_owner_template_order_cancelled'
            ];
            foreach ($textFields as $field) {
                if (isset($_POST[$field])) {
                    $this->settingModel->set($field, $_POST[$field]);
                }
            }

            if (isset($_POST['smtp_password']) && trim((string) $_POST['smtp_password']) !== '') {
                $this->settingModel->set('smtp_password', trim((string) $_POST['smtp_password']));
            }

            if (isset($_POST['payhere_merchant_secret']) && trim((string) $_POST['payhere_merchant_secret']) !== '') {
                $this->settingModel->set('payhere_merchant_secret', trim((string) $_POST['payhere_merchant_secret']));
            }

            if (isset($_POST['koko_api_key']) && trim((string) $_POST['koko_api_key']) !== '') {
                $this->settingModel->set('koko_api_key', trim((string) $_POST['koko_api_key']));
            }

            if (isset($_POST['koko_private_key']) && trim((string) $_POST['koko_private_key']) !== '') {
                $this->settingModel->set('koko_private_key', trim((string) $_POST['koko_private_key']));
            }

            if (isset($_POST['koko_callback_secret']) && trim((string) $_POST['koko_callback_secret']) !== '') {
                $this->settingModel->set('koko_callback_secret', trim((string) $_POST['koko_callback_secret']));
            }

            $this->settingModel->set(
                'koko_handling_fee_percentage',
                number_format(max(0, (float) ($_POST['koko_handling_fee_percentage'] ?? 0)), 2, '.', '')
            );

            if (isset($_POST['sms_api_key']) && trim((string) $_POST['sms_api_key']) !== '') {
                $this->settingModel->set('sms_api_key', trim((string) $_POST['sms_api_key']));
            }

            if (isset($_POST['cloudflare_r2_access_key_id']) && trim((string) $_POST['cloudflare_r2_access_key_id']) !== '') {
                $this->settingModel->set('cloudflare_r2_access_key_id', trim((string) $_POST['cloudflare_r2_access_key_id']));
            }

            if (isset($_POST['cloudflare_r2_secret_access_key']) && trim((string) $_POST['cloudflare_r2_secret_access_key']) !== '') {
                $this->settingModel->set('cloudflare_r2_secret_access_key', trim((string) $_POST['cloudflare_r2_secret_access_key']));
            }

            if (isset($_POST['recaptcha_v3_secret_key']) && trim((string) $_POST['recaptcha_v3_secret_key']) !== '') {
                $this->settingModel->set('recaptcha_v3_secret_key', trim((string) $_POST['recaptcha_v3_secret_key']));
            }

            $this->settingModel->set(
                'recaptcha_v3_min_score',
                number_format(min(0.9, max(0.1, (float) ($_POST['recaptcha_v3_min_score'] ?? 0.5))), 2, '.', '')
            );

            $this->settingModel->set('payhere_enabled', !empty($_POST['payhere_enabled']) ? '1' : '0');
            $this->settingModel->set('payhere_sandbox', !empty($_POST['payhere_sandbox']) ? '1' : '0');
            $this->settingModel->set('koko_enabled', !empty($_POST['koko_enabled']) ? '1' : '0');
            $this->settingModel->set('koko_sandbox', !empty($_POST['koko_sandbox']) ? '1' : '0');
            $this->settingModel->set('recaptcha_v3_enabled', !empty($_POST['recaptcha_v3_enabled']) ? '1' : '0');
            $this->settingModel->set('recaptcha_v3_admin_login', !empty($_POST['recaptcha_v3_admin_login']) ? '1' : '0');
            $this->settingModel->set('recaptcha_v3_checkout', !empty($_POST['recaptcha_v3_checkout']) ? '1' : '0');
            $this->settingModel->set('sms_enabled', !empty($_POST['sms_enabled']) ? '1' : '0');
            $this->settingModel->set('sms_owner_enabled', !empty($_POST['sms_owner_enabled']) ? '1' : '0');
            $this->settingModel->set('cloudflare_images_enabled', !empty($_POST['cloudflare_images_enabled']) ? '1' : '0');
            $this->settingModel->set('local_image_optimization_enabled', !empty($_POST['local_image_optimization_enabled']) ? '1' : '0');

            CloudflareR2Helper::clearCache();
            ImageHelper::clearConfigCache();

            // Owner Credentials Update / Create
            $ownerId = $_POST['owner_id'] ?? '';
            $newUsername = $_POST['owner_username'] ?? '';
            $newPass = $_POST['owner_password'] ?? '';
            $action = $_POST['owner_action'] ?? 'update';

            if ($action === 'create') {
                // Delete old owner if exists
                if (!empty($ownerId)) {
                    $this->userModel->delete($ownerId);
                }
                // Create new owner
                if (!empty($newUsername) && !empty($newPass)) {
                    $this->userModel->create($newUsername, $newPass, 'owner');
                }
            } else {
                // Update Existing
                if (!empty($ownerId)) {
                    if (!empty($newUsername)) {
                        $this->userModel->updateOwnerProfile($ownerId, $newUsername, $newPass);
                    }
                } else {
                    // Fallback: Create if not exists
                    if (!empty($newUsername) && !empty($newPass)) {
                        $this->userModel->create($newUsername, $newPass, 'owner');
                    }
                }
            }

            $this->redirect('settings/edit');
        }
    }

    public function testCloudflare()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('settings/edit');
            return;
        }

        $textFields = [
            'cloudflare_r2_account_id',
            'cloudflare_r2_bucket',
            'cloudflare_r2_public_base_url'
        ];

        foreach ($textFields as $field) {
            if (isset($_POST[$field])) {
                $this->settingModel->set($field, trim((string) $_POST[$field]));
            }
        }

        if (isset($_POST['cloudflare_r2_access_key_id']) && trim((string) $_POST['cloudflare_r2_access_key_id']) !== '') {
            $this->settingModel->set('cloudflare_r2_access_key_id', trim((string) $_POST['cloudflare_r2_access_key_id']));
        }

        if (isset($_POST['cloudflare_r2_secret_access_key']) && trim((string) $_POST['cloudflare_r2_secret_access_key']) !== '') {
            $this->settingModel->set('cloudflare_r2_secret_access_key', trim((string) $_POST['cloudflare_r2_secret_access_key']));
        }

        $this->settingModel->set('cloudflare_images_enabled', !empty($_POST['cloudflare_images_enabled']) ? '1' : '0');
        $this->settingModel->set('local_image_optimization_enabled', !empty($_POST['local_image_optimization_enabled']) ? '1' : '0');

        CloudflareR2Helper::clearCache();
        ImageHelper::clearConfigCache();

        $_SESSION['cloudflare_test_result'] = CloudflareR2Helper::testConnection();
        $this->redirect('settings/edit');
    }

    // 6. Global Styles Page
    public function styles()
    {
        $this->checkAuth();

        $styleKeys = [
            'primary_color',
            'secondary_color',
            'accent_red',
            'bg_color',
            'surface_color',
            'ink_color',
            'muted_color',
            'border_color',
            'header_bg',
            'header_text',
            'footer_bg',
            'footer_text',
            'footer_link',
            // Navigation
            'nav_mobile_bg',
            'nav_mobile_icon_color',
            'nav_mobile_active_color',
            'nav_desktop_bg',
            'nav_desktop_link_color',
            // Buttons
            'btn_addcart_bg',
            'btn_addcart_text',
            'btn_ordernow_bg',
            'btn_ordernow_text',
            'btn_cart_whatsapp_bg',
            'btn_cart_whatsapp_text',
            'btn_cart_cod_bg',
            'btn_cart_cod_text',
            'btn_cart_payhere_bg',
            'btn_cart_payhere_text',
            'btn_cart_koko_bg',
            'btn_cart_koko_text',
            // Floating Cart
            'floating_cart_bg',
            'floating_cart_text'
        ];
        $styles = $this->settingModel->getMultiple($styleKeys);

        $this->view('admin/settings/styles', [
            'title' => 'Global Styles',
            'styles' => $styles
        ]);
    }

    // 7. Save Styles
    public function updateStyles()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $key => $val) {
                $this->settingModel->set($key, $val);
            }
            $this->redirect('settings/styles');
        }
    }

    public function delivery()
    {
        $this->checkAuth();

        $settings = $this->settingModel->getMultiple([
            'delivery_apply_all_districts',
            'delivery_all_first_kg',
            'delivery_all_additional_kg'
        ]);
        $rates = $this->deliverySettingModel->getAllRates();

        $this->view('admin/settings/delivery', [
            'title' => 'Delivery Settings',
            'settings' => $settings,
            'rates' => $rates,
            'districts' => DeliveryHelper::districtList()
        ]);
    }

    public function updateDelivery()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->settingModel->set('delivery_apply_all_districts', !empty($_POST['delivery_apply_all_districts']) ? '1' : '0');
            $this->settingModel->set('delivery_all_first_kg', number_format((float) ($_POST['delivery_all_first_kg'] ?? 0), 2, '.', ''));
            $this->settingModel->set('delivery_all_additional_kg', number_format((float) ($_POST['delivery_all_additional_kg'] ?? 0), 2, '.', ''));

            $postedRates = $_POST['district_rates'] ?? [];
            $this->deliverySettingModel->saveRates(is_array($postedRates) ? $postedRates : []);

            $this->redirect('settings/delivery');
        }
    }

    // 8. Exit Developer Mode
    public function exit_dev()
    {
        $this->redirect('settings/edit');
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
            exit;
        }
    }
}
?>
