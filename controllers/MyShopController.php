<?php
/**
 * My Shop Controller
 */
require_once 'models/Setting.php';
require_once 'helpers/ImageHelper.php';

class MyShopController extends BaseController
{

    private $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    private function requireAdminSession()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
    }

    public function index()
    {
        $this->requireAdminSession();

        // Fetch all relevant settings
        $keys = [
            'shop_qr',
            'shop_logo',
            'shop_url',
            'review_link',
            'social_fb',
            'social_tiktok',
            'social_insta',
            'social_youtube',
            'social_whatsapp',
            'shop_owner_email',
            'courier_services_list',
            'hero_slide_1_image',
            'hero_slide_1_mobile_image',
            'hero_slide_1_link',
            'hero_slide_2_image',
            'hero_slide_2_mobile_image',
            'hero_slide_2_link',
            'hero_slide_3_image',
            'hero_slide_3_mobile_image',
            'hero_slide_3_link',
            'refund_policy_content',
            'terms_conditions_content',
            'privacy_policy_content',
            'cod_enabled',
            'whatsapp_ordering_enabled',
            'bank_transfer_enabled',
            'bank_transfer_details',
        ];

        $settings = $this->settingModel->getMultiple($keys);

        $this->view('admin/myshop/index', [
            'title' => 'My Shop',
            'settings' => $settings
        ]);
    }

    public function update()
    {
        $this->requireAdminSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Shop Owner can only update Socials and Review Link
            $allowedKeys = [
                'review_link',
                'social_fb',
                'social_tiktok',
                'social_insta',
                'social_youtube',
                'social_whatsapp',
                'shop_owner_email',
                'courier_services_list',
                'hero_slide_1_link',
                'hero_slide_2_link',
                'hero_slide_3_link',
                'refund_policy_content',
                'terms_conditions_content',
                'privacy_policy_content',
                'bank_transfer_details'
            ];

            foreach ($allowedKeys as $key) {
                if (isset($_POST[$key])) {
                    $this->settingModel->set($key, $_POST[$key]);
                }
            }

            $this->settingModel->set('cod_enabled', !empty($_POST['cod_enabled']) ? '1' : '0');
            $this->settingModel->set('whatsapp_ordering_enabled', !empty($_POST['whatsapp_ordering_enabled']) ? '1' : '0');
            $this->settingModel->set('bank_transfer_enabled', !empty($_POST['bank_transfer_enabled']) ? '1' : '0');

            for ($i = 1; $i <= 3; $i++) {
                $imageKey = 'hero_slide_' . $i . '_image';
                $mobileImageKey = 'hero_slide_' . $i . '_mobile_image';
                $removeKey = 'remove_hero_slide_' . $i . '_image';
                $removeMobileKey = 'remove_hero_slide_' . $i . '_mobile_image';

                if (!empty($_POST[$removeKey])) {
                    $oldUrl = $this->settingModel->get($imageKey);
                    if (!empty($oldUrl)) {
                        $this->deleteFile(basename($oldUrl));
                    }
                    $this->settingModel->set($imageKey, '');
                }

                if (isset($_FILES[$imageKey]) && $_FILES[$imageKey]['error'] === 0) {
                    $fileName = ImageHelper::storeUploadedFile($_FILES[$imageKey], 'hero_' . $i);
                    if ($fileName !== '') {
                        $oldUrl = $this->settingModel->get($imageKey);
                        if (!empty($oldUrl)) {
                            $this->deleteFile(basename((string) $oldUrl));
                        }

                        $this->settingModel->set($imageKey, ImageHelper::storedAssetUrl($fileName, BASE_URL . 'assets/uploads/' . $fileName));
                    }
                }

                if (!empty($_POST[$removeMobileKey])) {
                    $oldUrl = $this->settingModel->get($mobileImageKey);
                    if (!empty($oldUrl)) {
                        $this->deleteFile(basename($oldUrl));
                    }
                    $this->settingModel->set($mobileImageKey, '');
                }

                if (isset($_FILES[$mobileImageKey]) && $_FILES[$mobileImageKey]['error'] === 0) {
                    $fileName = ImageHelper::storeUploadedFile($_FILES[$mobileImageKey], 'hero_mobile_' . $i);
                    if ($fileName !== '') {
                        $oldUrl = $this->settingModel->get($mobileImageKey);
                        if (!empty($oldUrl)) {
                            $this->deleteFile(basename((string) $oldUrl));
                        }

                        $this->settingModel->set($mobileImageKey, ImageHelper::storedAssetUrl($fileName, BASE_URL . 'assets/uploads/' . $fileName));
                    }
                }
            }

            // Redirect back with success
            $this->redirect('myShop/index');
        }
    }
}
?>
