<?php
/**
 * Shop Controller
 * Handles Public Product Browsing
 */
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Variation.php';
require_once 'models/Setting.php';
require_once 'models/DeliverySetting.php';
require_once 'helpers/DeliveryHelper.php';
require_once 'helpers/ImageHelper.php';
require_once 'helpers/SeoHelper.php';

class ShopController extends BaseController
{
    private $productModel;
    private $categoryModel;
    private $settingModel;
    private $deliverySettingModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->settingModel = new Setting();
        $this->deliverySettingModel = new DeliverySetting();
    }

    // Shop Landing Page
    public function index()
    {
        $settings = $this->settingModel->getAllPairs();
        $products = $this->productModel->getLatest(24);
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('Shop', $settings),
            'seo_description' => SeoHelper::trimText('Browse the latest products from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'shop'),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Shop', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop')]
                ])
            ]
        ]);

        $this->view('customer/shop/shop', [
            'title' => 'Shop',
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

    // Single Product View
    public function product($id = null)
    {
        $id = (int) $id;
        if ($id <= 0 && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }
        if ($id <= 0) {
            http_response_code(404);
            require_once 'views/errors/404.php';
            return;
        }

        $product = $this->productModel->getById($id);

        if (!$product) {
            http_response_code(404);
            require_once 'views/errors/404.php';
            return;
        }

        $gallery = $this->productModel->getGalleryImages($id);
        $variations = $this->productModel->getVariations($id);
        $variantStockRows = $this->productModel->getVariantStockRows($id);
        foreach ($variantStockRows as &$variantRow) {
            $variantRow['image_url'] = !empty($variantRow['image_path'])
                ? ImageHelper::uploadUrl($variantRow['image_path'], '')
                : '';
        }
        unset($variantRow);

        $stockSnapshot = $this->productModel->getStockSnapshot($product);
        $relatedProducts = $this->productModel->getRelated($product['category_id'], $id, 4);
        $categories = $this->categoryModel->getAll();
        $settings = $this->settingModel->getAllPairs();

        $productImage = SeoHelper::productImageUrl($product['main_image'] ?? '');
        $productUrl = SeoHelper::absoluteUrl(BASE_URL . 'shop/product/' . $product['id']);
        $breadcrumbs = [
            ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
            ['name' => 'Shop', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop')]
        ];
        if (!empty($product['category_name'])) {
            $breadcrumbs[] = ['name' => $product['category_name'], 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/category/' . $product['category_id'])];
        }
        $breadcrumbs[] = ['name' => $product['title'], 'url' => $productUrl];

        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle($product['title'] ?? 'Product', $settings),
            'seo_description' => SeoHelper::trimText($product['description'] ?? ($product['title'] ?? ''), 160),
            'seo_canonical' => $productUrl,
            'seo_image' => $productImage,
            'seo_type' => 'product',
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema($breadcrumbs),
                SeoHelper::buildProductSchema($settings, $product, $productImage, $productUrl)
            ]
        ]);

        $this->view('customer/shop/product', [
            'title' => $product['title'] ?? 'Product',
            'product' => $product,
            'gallery' => $gallery,
            'variations' => $variations,
            'variant_stock_rows' => $variantStockRows,
            'stock_snapshot' => $stockSnapshot,
            'relatedProducts' => $relatedProducts,
            'categories' => $categories,
            'settings' => $settings,
            'deliveryDistricts' => DeliveryHelper::districtList(),
            'deliveryRatesMap' => $this->deliverySettingModel->getRatesMap(),
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }

    // --- New Desktop Pages (Task 6.3 Reuse Strategy) ---

    // 1. Sales Page (UI: "Discounts!")
    public function sales()
    {
        // Fetch On Sale Items using existing Model logic
        $products = $this->productModel->getAllOnSale();
        $categories = $this->categoryModel->getAll();
        $settings = $this->settingModel->getAllPairs();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('Discounts!', $settings),
            'seo_description' => SeoHelper::trimText('Browse sale products and discount deals from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'shop/sales'),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Discounts!', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/sales')]
                ])
            ]
        ]);

        $this->view('customer/shop/index', [
            'title' => 'Discounts!',
            'products' => $products,
            'categories' => $categories,
            'settings' => $settings,
            'isSpecialPage' => true, // Flag to trigger custom header in view
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }

    // 2. Featured Page (UI: "Featured Products")
    public function featured()
    {
        // Fetch Featured
        $products = $this->productModel->getFeatured(20); // Limit 20 for now
        $categories = $this->categoryModel->getAll();
        $settings = $this->settingModel->getAllPairs();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('Featured Products', $settings),
            'seo_description' => SeoHelper::trimText('Explore featured products from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'shop/featured'),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Featured Products', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/featured')]
                ])
            ]
        ]);

        $this->view('customer/shop/index', [
            'title' => 'Featured Products',
            'products' => $products,
            'categories' => $categories,
            'settings' => $settings,
            'isSpecialPage' => true,
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }

    // List All Categories Page
    public function categories()
    {
        $categories = $this->categoryModel->getAll();
        $settings = $this->settingModel->getAllPairs();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('All Categories', $settings),
            'seo_description' => SeoHelper::trimText('Browse all product categories from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'shop/categories'),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'All Categories', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/categories')]
                ])
            ]
        ]);

        $this->view('customer/shop/categories', [
            'title' => 'All Categories',
            'categories' => $categories,
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
    public function category($id)
    {
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            $this->redirect('shop/categories');
            return;
        }

        $settings = $this->settingModel->getAllPairs();
        $allCategories = $this->categoryModel->getAll();
        $subCategories = array_values(array_filter($allCategories, static function ($cat) use ($id) {
            return (string) ($cat['parent_id'] ?? '') === (string) $id;
        }));

        $categoryIds = [(int) $id];
        foreach ($subCategories as $subCategory) {
            $categoryIds[] = (int) ($subCategory['id'] ?? 0);
        }

        $products = $this->productModel->getFiltered(null, null, null, $categoryIds);
        $categoryCount = count($products);
        $categoryImage = ImageHelper::uploadUrl(
            $category['image'] ?? '',
            'https://via.placeholder.com/1200x1500?text=' . urlencode($category['name'] ?? 'Category')
        );

        $breadcrumbs = [
            ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
            ['name' => 'Shop', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop')],
            ['name' => 'Categories', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/categories')],
            ['name' => $category['name'] ?? 'Category', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/category/' . $id)]
        ];

        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle($category['name'] ?? 'Category', $settings),
            'seo_description' => SeoHelper::trimText('Browse products in ' . ($category['name'] ?? 'this category') . ' from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'shop/category/' . $id),
            'seo_image' => SeoHelper::productImageUrl($category['image'] ?? ''),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema($breadcrumbs)
            ]
        ]);

        $this->view('customer/shop/category_detail', [
            'title' => $category['name'] ?? 'Category',
            'category' => $category,
            'subCategories' => $subCategories,
            'products' => $products,
            'category_count' => $categoryCount,
            'settings' => $settings,
            'category_image' => $categoryImage,
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
        return;
    }
    // --- Desktop Home Tabs AJAX Handler ---
    public function tab_content()
    {
        $type = $_GET['type'] ?? 'new';
        $products = [];

        if ($type === 'new') {
            $products = $this->productModel->getLatest(12); // Grid of 12
        } elseif ($type === 'featured') {
            $products = $this->productModel->getFeatured(12);
        } elseif ($type === 'sale') {
            $products = $this->productModel->getOnSale(12); // Use getOnSale (limit 12) not All
        }

        if (empty($products)) {
            echo '<p style="text-align:center; padding:20px; color:#777;">No products found.</p>';
            return;
        }

        foreach ($products as $prod) {
            include 'views/customer/partials/product_card.php';
        }
    }
}
?>
