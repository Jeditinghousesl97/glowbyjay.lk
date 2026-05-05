<?php
require_once 'models/Setting.php';
require_once 'helpers/SeoHelper.php';

class ContactController extends BaseController
{
    private $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function index()
    {
        $settings = $this->settingModel->getAllPairs();
        $contactUrl = SeoHelper::absoluteUrl(BASE_URL . 'contact');
        $description = SeoHelper::trimText('Contact ' . SeoHelper::shopName($settings) . ' for support and order help.', 160);

        $this->view('customer/contact', [
            'title' => 'Contact Us',
            'settings' => $settings,
            'seo_title' => SeoHelper::pageTitle('Contact Us', $settings),
            'seo_description' => $description,
            'seo_canonical' => $contactUrl,
            'seo_robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Contact Us', 'url' => $contactUrl]
                ]),
                SeoHelper::buildWebPageSchema('ContactPage', 'Contact Us', $contactUrl, $description)
            ]
        ]);
    }
}
?>
