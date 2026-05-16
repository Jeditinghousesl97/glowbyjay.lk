<?php
require_once 'models/Product.php';
require_once 'models/Setting.php';
require_once 'helpers/ImageHelper.php';
require_once 'helpers/KokoPricingHelper.php';
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
        $limit = 20;
        $allProducts = $this->productModel->getAllOnSale();
        $products = array_slice($allProducts, 0, $limit);
        $totalProducts = count($allProducts);
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
            'discounts_limit' => $limit,
            'discounts_total_products' => $totalProducts,
            'discounts_has_more' => $totalProducts > count($products),
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

    public function loadMore()
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
        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $regularPrice = (float) ($product['price'] ?? 0);
            $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
            $discount = ($regularPrice > 0 && $salePrice < $regularPrice)
                ? (int) round((1 - ($salePrice / $regularPrice)) * 100)
                : 0;
            $productImage = ImageHelper::uploadUrl(
                $product['main_image'] ?? '',
                'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Discount')
            );
            $description = trim((string) ($product['short_description'] ?? $product['description'] ?? ''));
            ?>
            <article class="discount-card">
                <a class="discount-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $id) ?>">
                    <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars((string) ($product['title'] ?? 'Discount product')) ?>" loading="lazy" decoding="async">
                    <?php if ($discount > 0): ?>
                        <span class="discount-badge">-<?= $discount ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($product['free_shipping'])): ?>
                        <span class="discount-shipping">Free Shipping</span>
                    <?php endif; ?>
                </a>
                <div class="discount-body">
                    <span class="discount-chip"><?= htmlspecialchars((string) ($product['parent_category_name'] ?? $product['category_name'] ?? 'Discounted Item')) ?></span>
                    <h2 class="discount-name"><a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $id) ?>"><?= htmlspecialchars((string) ($product['title'] ?? 'Product')) ?></a></h2>
                    <div class="discount-price-row">
                        <span class="discount-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($salePrice, 0) ?></span>
                        <?php if ($discount > 0): ?>
                            <span class="discount-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($kokoEnabled): ?>
                        <?php
                        $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($product);
                        $kokoTeaser = $kokoBasePrice > 0 ? KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings ?? []) : null;
                        ?>
                        <?php if (!empty($kokoTeaser)): ?>
                            <div class="discount-koko-teaser" aria-label="KOKO installment plan">
                                <span class="discount-koko-text">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?></span>
                                <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="discount-koko-logo">
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($description !== ''): ?>
                        <p class="discount-desc"><?= htmlspecialchars($description) ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php
        }

        return (string) ob_get_clean();
    }
}
?>
