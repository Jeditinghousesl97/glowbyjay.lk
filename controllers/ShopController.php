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
require_once 'helpers/KokoPricingHelper.php';

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
        $storeTotalProducts = $this->productModel->countActiveProducts();
        $categories = $this->categoryModel->getAll();
        $searchQuery = trim((string) ($_GET['search'] ?? ''));
        $filterMin = isset($_GET['min']) && $_GET['min'] !== '' ? (float) $_GET['min'] : null;
        $filterMax = isset($_GET['max']) && $_GET['max'] !== '' ? (float) $_GET['max'] : null;
        $filterCategoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $filterCategoryIds = $filterCategoryId ? [$filterCategoryId] : [];
        $hasFilter = $searchQuery !== '' || $filterMin !== null || $filterMax !== null || !empty($filterCategoryIds);
        $limit = 20;
        if ($hasFilter) {
            $allFiltered = $this->productModel->getFiltered($filterMin, $filterMax, $searchQuery !== '' ? $searchQuery : null, $filterCategoryIds);
            $totalProducts = count($allFiltered);
            $products = array_slice($allFiltered, 0, $limit);
        } else {
            $products = $this->productModel->getLatestPaged($limit, 0);
            $totalProducts = $storeTotalProducts;
        }
        $hasMoreProducts = $totalProducts > count($products);
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
            'categories' => $categories,
            'settings' => $settings,
            'shop_limit' => $limit,
            'shop_total_products' => $totalProducts,
            'shop_store_total_products' => $storeTotalProducts,
            'shop_has_more' => $hasMoreProducts,
            'filter_search' => $searchQuery,
            'filter_min' => $filterMin,
            'filter_max' => $filterMax,
            'filter_category' => $filterCategoryId,
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }

    public function loadMore()
    {
        header('Content-Type: application/json; charset=utf-8');

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);
        $searchQuery = trim((string) ($_GET['search'] ?? ''));
        $filterMin = isset($_GET['min']) && $_GET['min'] !== '' ? (float) $_GET['min'] : null;
        $filterMax = isset($_GET['max']) && $_GET['max'] !== '' ? (float) $_GET['max'] : null;
        $filterCategoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $filterCategoryIds = $filterCategoryId ? [$filterCategoryId] : [];
        $hasFilter = $searchQuery !== '' || $filterMin !== null || $filterMax !== null || !empty($filterCategoryIds);

        $settings = $this->settingModel->getAllPairs();
        if ($hasFilter) {
            $allFiltered = $this->productModel->getFiltered($filterMin, $filterMax, $searchQuery !== '' ? $searchQuery : null, $filterCategoryIds);
            $total = count($allFiltered);
            $products = array_slice($allFiltered, $offset, $limit);
        } else {
            $products = $this->productModel->getLatestPaged($limit, $offset);
            $total = $this->productModel->countActiveProducts();
        }
        $nextOffset = $offset + count($products);
        $hasMore = $nextOffset < $total;

        $html = $this->renderShopCardsHtml($products, $settings);

        echo json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($products),
            'next_offset' => $nextOffset,
            'has_more' => $hasMore
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function renderShopCardsHtml(array $products, array $settings): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        $currency = (string) ($settings['currency_symbol'] ?? 'LKR');
        $isKokoEnabled = KokoPricingHelper::isEnabled($settings);
        $kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());

        ob_start();
        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $regularPrice = (float) ($product['price'] ?? 0);
            $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
            $hasDiscount = ($regularPrice > 0 && $salePrice > 0 && $salePrice < $regularPrice);
            $discount = $hasDiscount ? (int) round((1 - ($salePrice / $regularPrice)) * 100) : 0;
            $productImage = ImageHelper::uploadUrl(
                $product['main_image'] ?? '',
                'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Product')
            );
            $description = trim((string) ($product['short_description'] ?? $product['description'] ?? ''));
            $productUrl = $baseUrl . 'shop/product/' . $id;
            ?>
            <article class="shop-card">
                <a class="shop-media" href="<?= htmlspecialchars($productUrl) ?>">
                    <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars((string) ($product['title'] ?? 'Product')) ?>" loading="lazy" decoding="async">
                    <?php if ($hasDiscount && $discount > 0): ?>
                        <span class="shop-badge">-<?= $discount ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($product['free_shipping'])): ?>
                        <span class="shop-badge alt">Free Shipping</span>
                    <?php endif; ?>
                </a>
                <div class="shop-body">
                    <span class="shop-chip"><?= htmlspecialchars((string) ($product['parent_category_name'] ?? $product['category_name'] ?? 'Shop')) ?></span>
                    <h2 class="shop-name"><a href="<?= htmlspecialchars($productUrl) ?>"><?= htmlspecialchars((string) ($product['title'] ?? 'Product')) ?></a></h2>
                    <div class="shop-price-row">
                        <span class="shop-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($hasDiscount ? $salePrice : $regularPrice, 0) ?></span>
                        <?php if ($hasDiscount): ?>
                            <span class="shop-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($isKokoEnabled): ?>
                        <?php
                        $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($product);
                        $kokoTeaser = $kokoBasePrice > 0 ? KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings) : null;
                        ?>
                        <?php if (!empty($kokoTeaser)): ?>
                            <div class="shop-koko-teaser" aria-label="KOKO installment plan">
                                <span class="shop-koko-text">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?></span>
                                <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="shop-koko-logo">
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($description !== ''): ?>
                        <p class="shop-desc"><?= htmlspecialchars($description) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php
        }

        return (string) ob_get_clean();
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
        $limit = 20;
        $allProducts = $this->productModel->getAllOnSale();
        $products = array_slice($allProducts, 0, $limit);
        $totalProducts = count($allProducts);
        $categories = $this->categoryModel->getAll();
        $settings = $this->settingModel->getAllPairs();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('Discounts!', $settings),
            'seo_description' => SeoHelper::trimText('Browse sale products and discount deals from ' . SeoHelper::shopName($settings) . '.', 160),
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'discounts'),
            'seo_robots' => 'noindex,follow',
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => 'Discounts!', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'discounts')]
                ])
            ]
        ]);

        $this->view('customer/shop/index', [
            'title' => 'Discounts!',
            'products' => $products,
            'categories' => $categories,
            'settings' => $settings,
            'discounts_limit' => $limit,
            'discounts_total_products' => $totalProducts,
            'discounts_has_more' => $totalProducts > count($products),
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

    public function discountsLoadMore()
    {
        header('Content-Type: application/json; charset=utf-8');

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        $settings = $this->settingModel->getAllPairs();
        $allProducts = $this->productModel->getAllOnSale();
        $total = count($allProducts);
        $products = array_slice($allProducts, $offset, $limit);
        $nextOffset = $offset + count($products);
        $hasMore = $nextOffset < $total;

        $html = $this->renderDiscountCardsHtml($products, $settings);

        echo json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($products),
            'next_offset' => $nextOffset,
            'has_more' => $hasMore
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function renderDiscountCardsHtml(array $products, array $settings): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        $currency = (string) ($settings['currency_symbol'] ?? 'LKR');
        $kokoEnabled = KokoPricingHelper::isEnabled($settings ?? []);
        $kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());

        ob_start();
        foreach ($products as $prod) {
            $id = (int) ($prod['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $imagePath = ImageHelper::uploadUrl(
                $prod['main_image'] ?? '',
                'https://via.placeholder.com/700x875?text=' . urlencode($prod['title'] ?? 'Product')
            );
            $isOnSale = !empty($prod['sale_price']) && (float) $prod['sale_price'] < (float) $prod['price'];
            $description = trim((string) ($prod['short_description'] ?? $prod['description'] ?? ''));
            $description = preg_replace('/\s+/', ' ', $description ?? '');
            if (function_exists('mb_strimwidth')) {
                $description = mb_strimwidth((string) $description, 0, 120, '...');
            } elseif (strlen((string) $description) > 120) {
                $description = substr((string) $description, 0, 117) . '...';
            }
            ?>
            <article class="product-card">
                <a class="product-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $id) ?>">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars((string) ($prod['title'] ?? 'Product')) ?>" loading="lazy" decoding="async">
                    <?php if ($isOnSale): ?>
                        <span class="badge">Sale</span>
                    <?php elseif (!empty($prod['free_shipping'])): ?>
                        <span class="badge">Free Shipping</span>
                    <?php endif; ?>
                </a>
                <div class="product-meta">
                    <div>
                        <div class="product-category"><?= htmlspecialchars((string) ($prod['parent_category_name'] ?? $prod['category_name'] ?? 'Shop')) ?></div>
                        <h3 class="product-name"><a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $id) ?>"><?= htmlspecialchars((string) ($prod['title'] ?? 'Product')) ?></a></h3>
                    </div>
                    <div class="shop-price-row">
                        <?php if ($isOnSale): ?>
                            <span class="shop-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format((float) $prod['sale_price'], 0) ?></span>
                            <span class="shop-regular-price"><?= htmlspecialchars($currency) ?> <?= number_format((float) $prod['price'], 0) ?></span>
                        <?php else: ?>
                            <span class="shop-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format((float) $prod['price'], 0) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($kokoEnabled): ?>
                    <?php
                    $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($prod);
                    $kokoTeaser = $kokoBasePrice > 0 ? KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings ?? []) : null;
                    ?>
                    <?php if (!empty($kokoTeaser)): ?>
                        <div class="koko-installment-teaser" aria-label="KOKO installment plan">
                            <span class="koko-installment-text">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?></span>
                            <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="koko-installment-logo" style="height:16px;width:auto;flex-shrink:0;display:block;">
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <p class="product-desc"><?= htmlspecialchars((string) $description) ?></p>
            </article>
            <?php
        }

        return (string) ob_get_clean();
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
        $categoriesWithCounts = [];
        foreach ($categories as $categoryRow) {
            $catId = (int) ($categoryRow['id'] ?? 0);
            if ($catId <= 0) {
                continue;
            }
            $productsForCategory = $this->productModel->getFiltered(null, null, null, [$catId]);
            $categoryRow['product_count'] = count($productsForCategory);
            $categoriesWithCounts[] = $categoryRow;
        }
        usort($categoriesWithCounts, static function ($a, $b) {
            $countA = (int) ($a['product_count'] ?? 0);
            $countB = (int) ($b['product_count'] ?? 0);
            if ($countA === $countB) {
                return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            }
            return $countB <=> $countA;
        });
        $categories = $categoriesWithCounts;
        $settings = $this->settingModel->getAllPairs();

        $searchQuery = trim((string) ($_GET['search'] ?? ''));
        $filterMin = isset($_GET['min']) && $_GET['min'] !== '' ? (float) $_GET['min'] : null;
        $filterMax = isset($_GET['max']) && $_GET['max'] !== '' ? (float) $_GET['max'] : null;
        $filterCategoryIds = isset($_GET['cat']) ? array_values(array_filter(array_map('intval', (array) $_GET['cat']))) : [];
        $hasAnyFilter = $searchQuery !== '' || $filterMin !== null || $filterMax !== null || !empty($filterCategoryIds);

        if ($hasAnyFilter) {
            $products = $this->productModel->getFiltered($filterMin, $filterMax, $searchQuery !== '' ? $searchQuery : null, $filterCategoryIds);
            $resultTitle = $searchQuery !== '' ? ('Search Results: ' . $searchQuery) : 'Filtered Products';
            $resultDescription = $searchQuery !== ''
                ? SeoHelper::trimText('Search results for "' . $searchQuery . '" from ' . SeoHelper::shopName($settings) . '.', 160)
                : SeoHelper::trimText('Filtered products from ' . SeoHelper::shopName($settings) . '.', 160);
            $canonicalUrl = SeoHelper::absoluteUrl(BASE_URL . 'shop/categories');
            if (!empty($_SERVER['QUERY_STRING'])) {
                $canonicalUrl .= '?' . (string) $_SERVER['QUERY_STRING'];
            }

            $seo = SeoHelper::defaultSeo($settings, [
                'seo_title' => SeoHelper::pageTitle($resultTitle, $settings),
                'seo_description' => $resultDescription,
                'seo_canonical' => $canonicalUrl,
                'seo_robots' => 'noindex,follow',
                'seo_json_ld' => [
                    SeoHelper::buildOrganizationSchema($settings),
                    SeoHelper::buildWebsiteSchema($settings),
                    SeoHelper::buildBreadcrumbSchema([
                        ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                        ['name' => 'Categories', 'url' => SeoHelper::absoluteUrl(BASE_URL . 'shop/categories')]
                    ])
                ]
            ]);

            $this->view('customer/shop/index', [
                'title' => $resultTitle,
                'products' => $products,
                'categories' => $categories,
                'settings' => $settings,
                'search_query' => $searchQuery,
                'filter_search' => $searchQuery,
                'filter_min' => $filterMin,
                'filter_max' => $filterMax,
                'filter_category_ids' => $filterCategoryIds,
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

    public function searchSuggestions()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = trim((string) ($_GET['term'] ?? ''));
        if (mb_strlen($term) < 2) {
            echo json_encode(['success' => true, 'items' => []]);
            return;
        }

        $rawItems = $this->productModel->getSearchSuggestions($term, 8);
        $currency = (string) (($this->settingModel->getAllPairs()['currency_symbol'] ?? 'LKR'));
        $items = array_map(function ($item) use ($currency) {
            $regularPrice = (float) ($item['price'] ?? 0);
            $salePrice = (float) ($item['sale_price'] ?? 0);
            $effectivePrice = ($salePrice > 0 && $salePrice < $regularPrice) ? $salePrice : $regularPrice;
            return [
                'id' => (int) ($item['id'] ?? 0),
                'title' => (string) ($item['title'] ?? ''),
                'category_name' => (string) ($item['category_name'] ?? ''),
                'thumbnail_url' => ImageHelper::uploadUrl(
                    (string) ($item['main_image'] ?? ''),
                    'https://via.placeholder.com/120x120?text=' . urlencode((string) ($item['title'] ?? 'Product'))
                ),
                'url' => BASE_URL . 'shop/product/' . (int) ($item['id'] ?? 0),
                'price_label' => $effectivePrice > 0 ? ($currency . ' ' . number_format($effectivePrice, 0)) : ''
            ];
        }, $rawItems);

        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

        $allProducts = $this->productModel->getFiltered(null, null, null, $categoryIds);
        $categoryCount = count($allProducts);
        $limit = 20;
        $products = array_slice($allProducts, 0, $limit);
        $hasMoreProducts = $categoryCount > count($products);
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
            'category_limit' => $limit,
            'category_has_more' => $hasMoreProducts,
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

    public function categoryLoadMore($id)
    {
        header('Content-Type: application/json; charset=utf-8');

        $categoryId = (int) $id;
        if ($categoryId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        $category = $this->categoryModel->getById($categoryId);
        if (!$category) {
            echo json_encode(['success' => false, 'message' => 'Category not found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $allCategories = $this->categoryModel->getAll();
        $subCategories = array_values(array_filter($allCategories, static function ($cat) use ($categoryId) {
            return (string) ($cat['parent_id'] ?? '') === (string) $categoryId;
        }));

        $categoryIds = [$categoryId];
        foreach ($subCategories as $subCategory) {
            $categoryIds[] = (int) ($subCategory['id'] ?? 0);
        }

        $settings = $this->settingModel->getAllPairs();
        $allProducts = $this->productModel->getFiltered(null, null, null, $categoryIds);
        $total = count($allProducts);
        $products = array_slice($allProducts, $offset, $limit);
        $nextOffset = $offset + count($products);
        $hasMore = $nextOffset < $total;

        $html = $this->renderCategoryCardsHtml($products, $settings);

        echo json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($products),
            'next_offset' => $nextOffset,
            'has_more' => $hasMore
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function renderCategoryCardsHtml(array $products, array $settings): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        $currency = (string) ($settings['currency_symbol'] ?? 'LKR');
        $kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
        $kokoEnabled = KokoPricingHelper::isEnabled($settings ?? []);

        ob_start();
        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $regularPrice = (float) ($product['price'] ?? 0);
            $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
            $hasDiscount = ($regularPrice > 0 && $salePrice > 0 && $salePrice < $regularPrice);
            $discount = $hasDiscount ? (int) round((1 - ($salePrice / $regularPrice)) * 100) : 0;
            $productImage = ImageHelper::uploadUrl(
                $product['main_image'] ?? '',
                'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Product')
            );
            $description = trim((string) ($product['short_description'] ?? $product['description'] ?? ''));
            $chipCategories = [];
            if (!empty($product['parent_category_name'])) {
                $chipCategories[] = trim((string) $product['parent_category_name']);
            }
            if (!empty($product['category_name'])) {
                $chipCategories[] = trim((string) $product['category_name']);
            }
            $chipCategories = array_values(array_unique(array_filter($chipCategories, static function ($name) {
                return $name !== '';
            })));
            $chipLabel = !empty($chipCategories) ? implode(', ', $chipCategories) : 'Shop';
            $productUrl = $baseUrl . 'shop/product/' . $id;
            ?>
            <article class="category-card">
                <a class="category-media" href="<?= htmlspecialchars($productUrl) ?>">
                    <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars((string) ($product['title'] ?? 'Product')) ?>" loading="lazy" decoding="async">
                    <?php if ($hasDiscount && $discount > 0): ?>
                        <span class="category-badge">-<?= $discount ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($product['free_shipping'])): ?>
                        <span class="category-shipping">Free Shipping</span>
                    <?php endif; ?>
                </a>
                <div class="category-body">
                    <span class="category-chip"><?= htmlspecialchars($chipLabel) ?></span>
                    <h2 class="category-name"><a href="<?= htmlspecialchars($productUrl) ?>"><?= htmlspecialchars((string) ($product['title'] ?? 'Product')) ?></a></h2>
                    <div class="category-price-row">
                        <span class="category-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($salePrice, 0) ?></span>
                        <?php if ($hasDiscount): ?>
                            <span class="category-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($kokoEnabled): ?>
                        <?php
                        $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($product);
                        $kokoTeaser = $kokoBasePrice > 0 ? KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings ?? []) : null;
                        ?>
                        <?php if (!empty($kokoTeaser)): ?>
                            <div class="category-koko-teaser" aria-label="KOKO installment plan">
                                <span class="category-koko-text">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?></span>
                                <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="category-koko-logo">
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($description !== ''): ?>
                        <p class="category-desc"><?= htmlspecialchars($description) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php
        }

        return (string) ob_get_clean();
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
