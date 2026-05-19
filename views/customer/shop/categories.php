<?php
$hide_mobile_welcome = true;
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'models/Product.php';
require_once 'views/layouts/customer_layout.php';
$categories = is_array($categories ?? null) ? $categories : [];
if (!empty($categories) && is_array($categories)) {
    $needsCountHydration = false;
    foreach ($categories as $categoryRow) {
        if (!array_key_exists('product_count', $categoryRow)) {
            $needsCountHydration = true;
            break;
        }
    }

    if ($needsCountHydration) {
        $productModel = new Product();
        foreach ($categories as &$categoryRow) {
            $catId = (int) ($categoryRow['id'] ?? 0);
            if ($catId <= 0) {
                $categoryRow['product_count'] = 0;
                continue;
            }
            $productsForCategory = $productModel->getFiltered(null, null, null, [$catId]);
            $categoryRow['product_count'] = is_array($productsForCategory) ? count($productsForCategory) : 0;
        }
        unset($categoryRow);
    }

    usort($categories, static function ($a, $b) {
        $countA = (int) ($a['product_count'] ?? 0);
        $countB = (int) ($b['product_count'] ?? 0);
        if ($countA === $countB) {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        }
        return $countB <=> $countA;
    });
}
customer_layout_start([
    'seo_title' => $seo_title ?? ($title ?? ''),
    'seo_description' => $seo_description ?? '',
    'seo_image' => $seo_image ?? '',
    'seo_canonical' => $seo_canonical ?? '',
    'seo_type' => $seo_type ?? 'website',
    'seo_robots' => $seo_robots ?? '',
    'seo_json_ld' => $seo_json_ld ?? []
]);
?>

<?php include 'views/customer/partials/shop_theme_styles.php'; ?>
<style>
    .shop-section-title{
        font-family:sans-serif !important;
    }
    @media (max-width: 1023px){
        .shop-page-shell{
            padding-left:14px !important;
            padding-right:14px !important;
        }
    }
</style>

<main class="shop-page">
    <div class="shop-page-shell">
        <section class="shop-section" id="categoryGrid">
            <div class="shop-section-head">
                <div class="shop-section-head-left">
                    <span class="shop-label">Collections</span>
                    <h2 class="shop-section-title">Browse all categories</h2>
                    <p class="shop-section-copy">
                        Discover categories organized by popularity, so you can quickly find the most loved collections first.
                        Explore essentials, trending picks, and signature ranges all in one place.
                    </p>
                </div>
            </div>

            <div class="shop-mini-grid" style="margin-top: 0;">
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $img = ImageHelper::uploadUrl(
                        $cat['image'] ?? '',
                        'https://via.placeholder.com/900x700?text=' . urlencode($cat['name'] ?? 'Category')
                    );
                    ?>
                    <a class="shop-mini-panel" href="<?= htmlspecialchars(BASE_URL . 'shop/category/' . $cat['id']) ?>">
                        <?= ImageHelper::renderResponsivePicture(
                            $cat['image'] ?? '',
                            $img,
                            [
                                'alt' => $cat['name'] ?? 'Category',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'category_card'
                        ) ?>
                        <div class="overlay bottom-right">
                            <h3><?= htmlspecialchars($cat['name'] ?? 'Category') ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php customer_layout_end(); ?>
