<?php
require_once 'models/Product.php';
require_once 'models/Setting.php';
require_once 'helpers/SeoHelper.php';

class DiscountsController extends BaseController
{
    private $productModel;
    private $settingModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->settingModel = new Setting();
    }

    public function index()
    {
        $settings = $this->settingModel->getAllPairs();
        $products = $this->productModel->getAllOnSale();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('Discounts', $settings),
            'seo_description' => SeoHelper::trimText('Browse the latest discounts from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'discounts'),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Discounts', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'discounts')]
                ])
            ]
        ]);

        $this->view('customer/discounts', [
            'title' => 'Discounts',
            'products' => $products,
            'settings' => $settings,
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }
}
?>
