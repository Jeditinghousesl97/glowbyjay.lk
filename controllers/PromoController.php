<?php
require_once 'models/Setting.php';
require_once 'helpers/ImageHelper.php';

class PromoController extends BaseController
{
    private $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function index()
    {
        $this->checkAuth();

        $promoKeys = [
            'promo_enabled',
            'promo_image',
            'promo_link',
            'promo_open_new_tab',
            'entrance_popup_enabled',
            'entrance_popup_image'
        ];
        $promo = $this->settingModel->getMultiple($promoKeys);

        $this->view('admin/promo/index', [
            'title' => 'Promo Popup Settings',
            'promo' => $promo
        ]);
    }

    public function update()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['promo_image']) && (int) ($_FILES['promo_image']['error'] ?? 4) === 0) {
                $fileName = ImageHelper::storeUploadedFile($_FILES['promo_image'], 'promo');
                if ($fileName !== '') {
                    $oldUrl = $this->settingModel->get('promo_image');
                    if (!empty($oldUrl)) {
                        $oldFile = basename((string) parse_url((string) $oldUrl, PHP_URL_PATH));
                        $this->deleteFile($oldFile);
                    }

                    $this->settingModel->set(
                        'promo_image',
                        ImageHelper::storedAssetUrl($fileName, BASE_URL . 'assets/uploads/' . $fileName)
                    );
                }
            }

            if (isset($_FILES['entrance_popup_image']) && (int) ($_FILES['entrance_popup_image']['error'] ?? 4) === 0) {
                $fileName = ImageHelper::storeUploadedFile($_FILES['entrance_popup_image'], 'promo');
                if ($fileName !== '') {
                    $oldUrl = $this->settingModel->get('entrance_popup_image');
                    if (!empty($oldUrl)) {
                        $oldFile = basename((string) parse_url((string) $oldUrl, PHP_URL_PATH));
                        $this->deleteFile($oldFile);
                    }

                    $this->settingModel->set(
                        'entrance_popup_image',
                        ImageHelper::storedAssetUrl($fileName, BASE_URL . 'assets/uploads/' . $fileName)
                    );
                }
            }

            $promoLink = trim((string) ($_POST['promo_link'] ?? ''));
            $this->settingModel->set('promo_link', $promoLink);
            $this->settingModel->set('promo_enabled', !empty($_POST['promo_enabled']) ? '1' : '0');
            $this->settingModel->set('promo_open_new_tab', !empty($_POST['promo_open_new_tab']) ? '1' : '0');
            $this->settingModel->set('entrance_popup_enabled', !empty($_POST['entrance_popup_enabled']) ? '1' : '0');
        }

        $this->redirect('promo/index');
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
