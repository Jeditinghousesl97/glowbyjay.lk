<?php
$hide_mobile_welcome = true;
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once 'views/layouts/customer_layout.php';
customer_layout_start();
?>

<?php include 'views/customer/partials/shop_theme_styles.php'; ?>

<main class="shop-page">
    <div class="shop-page-shell">
        <section class="shop-section" id="categoryGrid">
            <div class="shop-section-head">
                <div class="shop-section-head-left">
                    <span class="shop-label">Collections</span>
                    <h2 class="shop-section-title">Browse all categories</h2>
                    <p class="shop-section-copy">
                        Explore our full range of styles and collections. 
                        Find everything you need in one place, 
                        from everyday essentials to trend-driven fashion.
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
