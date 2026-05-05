<?php
// Product Card Partial
// Expects $prod array available AND $settings array (global or passed)
// We need to ensure $settings is available here. In Home view it is.
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';

$currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'LKR';
// Fallback to LKR if not set, but database usually has it.

$imagePath = ImageHelper::uploadUrl(
    $prod['main_image'] ?? '',
    'https://via.placeholder.com/300?text=' . urlencode($prod['title'])
);
$kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());

$isOnSale = !empty($prod['sale_price']) && $prod['sale_price'] < $prod['price'];
$kokoTeaser = null;
if (KokoPricingHelper::isEnabled($settings ?? [])) {
    $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($prod);
    if ($kokoBasePrice > 0) {
        $kokoTeaser = KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings ?? []);
    }
}
?>

<div class="product-card">
    <div class="product-thumb-container">
        <a href="<?= BASE_URL ?>shop/product/<?= $prod['id'] ?>">
            <?= ImageHelper::renderResponsivePicture(
                $prod['main_image'] ?? '',
                $imagePath,
                [
                    'class' => 'product-thumb',
                    'alt' => $prod['title'] ?? 'Product',
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'fetchpriority' => 'low'
                ],
                'product_card'
            ) ?>
        </a>

        <?php if ($isOnSale): ?>
            <div class="sale-badge">SALE</div>
        <?php endif; ?>

        <?php if (!empty($prod['free_shipping'])): ?>
            <div class="free-shipping-badge free-shipping-badge-overlay">Free Shipping</div>
        <?php endif; ?>

        <!-- Cart Icon (Top Right, Black Circle) -->
        <?php
        $jsTitle = addslashes($prod['title']);
        $jsPrice = (!empty($prod['sale_price']) && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price'];
        $jsImg = $imagePath;
        ?>
        <div class="cart-btn-overlay"
            onclick="addToCart(<?= $prod['id'] ?>, '<?= $jsTitle ?>', <?= $jsPrice ?>, '<?= $jsImg ?>')">
            <i class="fas fa-shopping-cart" style="font-size: 12px;"></i>
        </div>
    </div>

    <div class="product-info">
        <h3 class="product-name">
            <a href="<?= BASE_URL ?>shop/product/<?= $prod['id'] ?>"><?= htmlspecialchars($prod['title']) ?></a>
        </h3>

        <div class="product-price-box">
            <?php if ($isOnSale): ?>
                <span class="old-price"><?= $currency ?>     <?= number_format($prod['price'], 0) ?></span>
                <span class="current-price price-sale"><?= $currency ?>     <?= number_format($prod['sale_price'], 0) ?></span>
            <?php else: ?>
                <span class="current-price"><?= $currency ?>     <?= number_format($prod['price'], 0) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($kokoTeaser)): ?>
            <div class="koko-installment-teaser" aria-label="KOKO installment plan">
                <span class="koko-installment-text">
                    or 3 x <?= $currency ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?>
                </span>
                <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="koko-installment-logo" style="height:16px;width:auto;flex-shrink:0;display:block;">
            </div>
        <?php endif; ?>

        <!-- Category Info (Parent | Child) -->
        <div class="product-category">
            <?php
            $catName = htmlspecialchars($prod['category_name'] ?? '');
            $parentName = htmlspecialchars($prod['parent_category_name'] ?? '');

            if (!empty($parentName) && !empty($catName)) {
                echo $parentName . ' | ' . $catName;
            } else {
                echo $catName;
            }
            ?>
        </div>
    </div>
</div>
