<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';
require_once ROOT_PATH . 'helpers/KokoGateway.php';
require_once ROOT_PATH . 'helpers/RecaptchaHelper.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$currency = $settings['currency_symbol'] ?? 'LKR';
$productUnitPrice = (!empty($product['sale_price']) && (float) $product['sale_price'] < (float) $product['price'])
    ? (float) $product['sale_price']
    : (float) $product['price'];
$productRegularPrice = (float) ($product['price'] ?? 0);
$productSalePrice = (!empty($product['sale_price']) && (float) $product['sale_price'] < $productRegularPrice)
    ? (float) $product['sale_price']
    : null;
$kokoTeaserData = KokoPricingHelper::isEnabled($settings ?? [])
    ? KokoPricingHelper::getInstallmentData($productUnitPrice, $settings ?? [])
    : null;

$shopWhatsappTarget = preg_replace('/[^0-9]/', '', (string) ($settings['shop_whatsapp'] ?? ''));
if ($shopWhatsappTarget === '') {
    $shopWhatsappTarget = preg_replace('/[^0-9]/', '', (string) ($settings['social_whatsapp'] ?? ''));
}
$whatsappEnabled = !empty($settings['whatsapp_ordering_enabled']) && $shopWhatsappTarget !== '';
$codEnabled = !empty($settings['cod_enabled']);
$bankTransferEnabled = !empty($settings['bank_transfer_enabled']) && trim((string) ($settings['bank_transfer_details'] ?? '')) !== '';
$payhereReady = !empty($settings['payhere_enabled']) && trim((string) ($settings['payhere_merchant_id'] ?? '')) !== '' && trim((string) ($settings['payhere_merchant_secret'] ?? '')) !== '';
$kokoReady = class_exists('KokoGateway') && KokoGateway::isConfigured($settings);
$recaptchaCheckoutEnabled = RecaptchaHelper::shouldProtectCheckout($settings);
$recaptchaSiteKey = $recaptchaCheckoutEnabled ? RecaptchaHelper::siteKey($settings) : '';

$kokoLogoUrl = $baseUrl . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
$mainImageUrl = ImageHelper::uploadUrl(
    $product['main_image'] ?? '',
    'https://via.placeholder.com/960x1200?text=' . urlencode($product['title'] ?? 'Product')
);
$sizeGuideImage = ImageHelper::uploadUrl($product['size_guide_image'] ?? '', '');
$hasSizeGuide = !empty($product['size_guide_id']) || trim((string) $sizeGuideImage) !== '';
$shortDescription = trim((string) ($product['short_description'] ?? ''));
$longDescription = trim((string) ($product['description'] ?? ''));
$categoryName = trim((string) ($product['category_name'] ?? ''));
$parentCategoryName = trim((string) ($product['parent_category_name'] ?? ''));
$categoryMap = [];
if (!empty($categories) && is_array($categories)) {
    foreach ($categories as $categoryRow) {
        $categoryMap[(int) ($categoryRow['id'] ?? 0)] = $categoryRow;
    }
}
$productCategoryId = (int) ($product['category_id'] ?? 0);
$productCategory = $productCategoryId > 0 && isset($categoryMap[$productCategoryId]) ? $categoryMap[$productCategoryId] : null;
$parentCategoryId = $productCategory && !empty($productCategory['parent_id']) ? (int) $productCategory['parent_id'] : 0;
$parentCategoryLink = $parentCategoryId > 0 && isset($categoryMap[$parentCategoryId])
    ? ['id' => $parentCategoryId, 'name' => (string) ($categoryMap[$parentCategoryId]['name'] ?? '')]
    : null;
$currentCategoryLink = $productCategoryId > 0 ? ['id' => $productCategoryId, 'name' => $categoryName !== '' ? $categoryName : (string) ($productCategory['name'] ?? '')] : null;
$stockSnapshot = $stock_snapshot ?? [];
$stockStatus = (string) ($stockSnapshot['status'] ?? 'in_stock');
$stockStatusLabels = [
    'in_stock' => 'In Stock',
    'low_stock' => 'Low Stock',
    'out_of_stock' => 'Out of Stock'
];
$stockStatusLabel = $stockStatusLabels[$stockStatus] ?? 'In Stock';
$stockStatusQty = array_key_exists('available_qty', $stockSnapshot) ? $stockSnapshot['available_qty'] : null;
$hasVariations = !empty($variations) && is_array($variations);
$initialPurchaseDisabled = $hasVariations || $stockStatus === 'out_of_stock' || ($stockStatus === 'track_stock' && (int) ($stockStatusQty ?? 0) <= 0);
$paymentModeLogos = [];
if ($whatsappEnabled) {
    $paymentModeLogos[] = ['label' => 'WhatsApp', 'src' => $baseUrl . 'assets/icons/payment-gateways/whatsapp-order.png', 'alt' => 'WhatsApp order'];
}
if ($codEnabled) {
    $paymentModeLogos[] = ['label' => 'COD', 'src' => $baseUrl . 'assets/icons/payment-gateways/cod.png', 'alt' => 'Cash on delivery'];
}
if (!empty($settings['payhere_enabled'])) {
    $paymentModeLogos[] = ['label' => 'Card Payments', 'src' => $baseUrl . 'assets/icons/payment-gateways/payhere.png', 'alt' => 'Card Payments'];
}
if (!empty($settings['koko_enabled'])) {
    $paymentModeLogos[] = ['label' => 'KOKO Payments', 'src' => $baseUrl . 'assets/icons/payment-gateways/koko.png', 'alt' => 'KOKO Payments'];
}
if ($bankTransferEnabled) {
    $paymentModeLogos[] = ['label' => 'Bank Transfer', 'src' => $baseUrl . 'assets/icons/payment-gateways/bank.png', 'alt' => 'Bank transfer'];
}
$gallerySlides = [
    [
        'path' => (string) ($product['main_image'] ?? ''),
        'url' => $mainImageUrl,
        'alt' => (string) ($product['title'] ?? 'Product image')
    ]
];
if (!empty($gallery) && is_array($gallery)) {
    foreach ($gallery as $galleryIndex => $gImg) {
        $gUrl = ImageHelper::uploadUrl($gImg, '');
        if ($gUrl === '') {
            continue;
        }
        $gallerySlides[] = [
            'path' => (string) $gImg,
            'url' => $gUrl,
            'alt' => (string) ($product['title'] ?? 'Product') . ' gallery image ' . ((int) $galleryIndex + 1)
        ];
    }
}
$galleryCount = count($gallerySlides);
$hasGalleryCarousel = $galleryCount > 1;
$deliveryApplyAllDistricts = !empty($settings['delivery_apply_all_districts']);
$deliveryAllFirstKg = (float) ($settings['delivery_all_first_kg'] ?? 0);
$deliveryAllAdditionalKg = (float) ($settings['delivery_all_additional_kg'] ?? 0);
$faviconUrl = ImageHelper::settingsImageUrl(
    (string) ($settings['shop_favicon'] ?? ''),
    str_replace('/Ecom-CMS/', BASE_URL, (string) ($settings['shop_favicon'] ?? ''))
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo_title ?? ($title ?? 'Product')) ?></title>
    <?php if (!empty($faviconUrl)): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconUrl) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($recaptchaCheckoutEnabled && $recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptchaSiteKey) ?>"></script>
    <?php endif; ?>
    <style>
        :root{--primary:var(--accent, #b9000b);--primary-strong:var(--accent-red, #e31a1a);--surface-low:var(--surface-soft, #fafafa);--surface-mid:var(--surface-soft, #fafafa);--ink:#1c1b1b;--muted:#6d6665;--shadow:0 24px 60px rgba(28,27,27,.08);--shadow-soft:0 14px 30px rgba(28,27,27,.06)}
        *{box-sizing:border-box} html{scroll-behavior:smooth;background:var(--surface)} body{margin:0;font-family:"Manrope",sans-serif;background:var(--surface);color:var(--ink)}
        *,*::before,*::after{border-radius:0 !important}
        h1,h2,h3,h4,h5{font-family:"Noto Serif",serif;font-weight:400;margin:0}
        a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
        .page{overflow:hidden}
        .container{width:min(1600px,calc(100% - 96px));margin:0 auto}
        .main{padding-top:0}
        .product-hero{padding:34px 0 58px}
        .product-shell{display:grid;grid-template-columns:minmax(0,1.18fr) minmax(320px,.82fr);gap:28px;align-items:start}
        .visual-card,.summary-card,.product-card{box-shadow:var(--shadow);background:var(--surface)}
        .visual-card{overflow:hidden;position:relative}.back-chip{position:absolute;top:16px;left:16px;z-index:4;width:42px;height:42px;border-radius:0;display:flex;align-items:center;justify-content:center;background:var(--surface);box-shadow:0 8px 18px rgba(0,0,0,.08);font-size:18px;color:var(--ink)}
        .gallery-slider{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none}.gallery-slider::-webkit-scrollbar{display:none}
        .gallery-slide{min-width:100%;scroll-snap-align:start;padding:16px;background:var(--surface);display:flex;align-items:center;justify-content:center;min-height:700px}
        .gallery-slide picture,.gallery-slide img{display:block;width:100%;height:auto}
        .gallery-slide img{max-height:100%;object-fit:contain;object-position:center}
        .gallery-open-btn{all:unset;display:block;width:100%;height:100%;cursor:zoom-in}
        .gallery-open-btn picture,.gallery-open-btn img{display:block;width:100%;height:100%}
        .gallery-open-btn img{object-fit:contain;object-position:center}
        .gallery-dots{display:flex;gap:8px;justify-content:center;padding:14px 18px 14px;background:var(--surface)}.dot{width:9px;height:9px;border-radius:0;border:0;background:rgba(28,27,27,.16)}.dot.active{background:var(--primary)}
        .gallery-thumbs{display:grid;grid-template-columns:repeat(auto-fit,minmax(78px,1fr));gap:10px;padding:0 18px 18px;background:var(--surface)}
        .gallery-thumb{appearance:none;border:1px solid transparent;background:var(--surface);border-radius:0;overflow:hidden;padding:0;aspect-ratio:1/1;cursor:pointer;transition:border-color .2s ease,transform .2s ease,box-shadow .2s ease}
        .gallery-thumb:hover{transform:translateY(-1px)}
        .gallery-thumb.active{border-color:var(--primary);box-shadow:0 0 0 2px rgba(185,0,11,.12)}
        .gallery-thumb picture,.gallery-thumb img{display:block;width:100%;height:100%}
        .gallery-thumb img{object-fit:cover;object-position:center}
        .visual-copy{padding:22px 24px 24px}.visual-copy .section-title{font-family:"Noto Serif",serif;font-size:32px;letter-spacing:-.04em;line-height:1.02}.visual-copy p{margin:12px 0 0;color:var(--muted);line-height:1.8}
        .summary-card{padding:28px;position:sticky;top:104px}.summary-topline{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}.chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:0;background:var(--surface);font-size:10px;letter-spacing:.2em;text-transform:uppercase;color:rgba(28,27,27,.68)}
        .pd-breadcrumb{font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:rgba(28,27,27,.46);line-height:1.5}.pd-title{font-family:"Noto Serif",serif;font-size:clamp(28px,3.2vw,44px);line-height:1.05;margin-top:10px}
        .price-row{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin:18px 0 8px}.price-stack{display:grid;gap:10px}.pd-prices{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;font-size:18px}.pd-old-price{text-decoration:line-through;color:rgba(28,27,27,.4);font-size:14px}.pd-sale-price{font-size:28px;font-weight:800;color:var(--ink)}
        .koko-installment-teaser{display:inline-flex;align-items:center;gap:12px;padding:12px 16px;border-radius:0;background:var(--surface);color:#166534;font-size:13px;font-weight:800;line-height:1.2;flex-wrap:nowrap;white-space:nowrap;overflow:hidden}.koko-installment-logo{height:18px;width:auto}
        .koko-installment-teaser-single{font-size:15px !important;gap:12px !important;padding:12px 16px !important}
        .koko-installment-teaser-single .koko-installment-logo{height:20px !important;width:auto !important}
        .btn-size-guide{display:inline-flex;align-items:center;gap:8px;min-height:42px;height:42px;border:1px solid var(--ink);background:var(--surface);color:var(--ink);font-weight:800;letter-spacing:.18em;text-transform:uppercase;font-size:10px;padding:0 14px;cursor:pointer;white-space:nowrap;width:max-content;border-radius:0;box-shadow:none}
        .btn-size-guide i{font-size:14px;line-height:1}
        .size-guide-lightbox .modal-content{position:relative;padding:0;max-width:min(92vw,760px);background:var(--surface);box-shadow:0 22px 50px rgba(0,0,0,.22)}
        .size-guide-lightbox .size-guide-close{position:absolute;top:12px;right:12px;cursor:pointer;z-index:100;background:rgba(255,255,255,0.92);border-radius:50%;padding:5px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.12)}
        .size-guide-lightbox .size-guide-close i{color:#111;font-size:15px}
        .size-guide-lightbox picture,.size-guide-lightbox img{display:block;width:100%;height:auto;max-width:100%;border-radius:0 !important;object-fit:contain}
        .summary-short-desc{margin:18px 0 0;color:var(--muted);line-height:1.8;font-size:14px}
        .summary-desc{margin:20px 0 22px;color:var(--muted);line-height:1.85}
        .var-section{margin-bottom:18px}.var-label{display:block;margin-bottom:10px;font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:rgba(28,27,27,.55)}.var-pills{display:flex;flex-wrap:wrap;gap:10px}
        .var-pill{padding:10px 14px;border:1px solid rgba(28,27,27,.12);border-radius:0;cursor:pointer;background:var(--surface);font-size:13px}.var-pill.active{border-color:var(--accent-red, var(--primary));background:color-mix(in srgb, var(--accent-red, var(--primary)) 8%, #ffffff);color:var(--accent-red, var(--primary));font-weight:700}.var-pill.is-disabled{opacity:.35;cursor:not-allowed;pointer-events:none;filter:grayscale(.15)}
        .stock-filter-actions{display:flex;justify-content:flex-end;margin:8px 0 18px}.stock-clear-btn{border:1px solid #f2b26b;background:#ff9f43;color:#fff;border-radius:0;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer}
        #productStockNotice{margin:0 0 18px;padding:12px 14px;border-radius:0;font-size:13px;font-weight:700}
        .qty-row{display:flex;align-items:center;justify-content:flex-start;gap:16px;flex-wrap:wrap;margin:20px 0 10px}
        .qty-box{display:flex;align-items:center;border:1px solid var(--ink);border-radius:0;background:var(--surface);height:42px;overflow:hidden}.qty-box button{border:none;background:transparent;width:40px;height:100%;font-size:18px;cursor:pointer;color:var(--ink);display:flex;align-items:center;justify-content:center}.qty-box button:first-child{border-right:1px solid var(--ink)}.qty-box button:last-child{border-left:1px solid var(--ink)}.qty-box input{width:48px;height:100%;text-align:center;border:none;font-weight:700;font-size:14px;outline:none;color:var(--ink);padding:0}
        .summary-inline-action{margin:0}
        .pd-bottom-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:22px}
        .btn-action{
            min-height:54px;
            border:0;
            border-radius:0;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            padding:0 18px;
            font-size:12px;
            font-weight:800;
            letter-spacing:.18em;
            text-transform:uppercase;
            cursor:pointer;
            transition:transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        }
        .btn-action:hover{transform:translateY(-1px)}
        .btn-order-now{background:var(--btn-ordernow-bg, var(--primary));color:var(--btn-ordernow-text, #fff);box-shadow:0 12px 24px rgba(185,0,11,.16)}
        .btn-cart{background:var(--btn-addcart-bg, var(--ink));color:var(--btn-addcart-text, #fff);box-shadow:0 12px 24px rgba(28,27,27,.14)}
        .action-note{margin-top:14px;font-size:12px;line-height:1.7;color:var(--muted)}
        .detail-card{background:var(--surface);box-shadow:var(--shadow-soft);padding:24px}.detail-card h3{font-size:26px;margin-bottom:14px;font-family:"Noto Serif",serif}.detail-card p{margin:0;color:var(--muted);line-height:1.9}.features-list{display:grid;gap:10px;margin-top:16px}.feature-line{display:flex;gap:10px;align-items:flex-start;color:var(--ink);font-size:13px;line-height:1.7}.feature-line i{color:var(--accent-red, var(--primary));margin-top:3px}
        .section{padding:72px 0}.section-surface-low{background:var(--surface-low)}.section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:28px}.section-head-left{max-width:720px}.label{display:block;margin-bottom:6px;font-size:11px;letter-spacing:.26em;text-transform:uppercase;color:rgba(28,27,27,.55)}.section-title{font-family:"Noto Serif",serif;font-size:clamp(34px,4vw,54px);letter-spacing:-.04em;line-height:1.02}.section-link{font-size:11px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:inherit;display:inline-flex;align-items:center;gap:10px}.section-link::after{content:"";width:44px;height:1px;background:currentColor;opacity:.35}
        .related-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}.product-media{aspect-ratio:3/4;overflow:hidden;background:var(--surface);display:flex;align-items:center;justify-content:center}.product-media img{width:100%;height:100%;object-fit:contain;object-position:center;transition:transform .7s ease}.product-card:hover .product-media img{transform:scale(1.03)}.product-body{padding:18px 16px 20px}.product-top{display:flex;align-items:start;justify-content:space-between;gap:18px;margin-bottom:10px}.product-name{font-family:"Noto Serif",serif;font-size:18px;line-height:1.22}.product-price{font-size:12px;font-weight:800;white-space:nowrap}.product-desc{color:var(--muted);font-size:13px;line-height:1.65;margin:0 0 16px}.product-btn{width:100%;min-height:48px;border:0;border-radius:0;background:transparent;color:var(--ink);font-size:11px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;box-shadow:inset 0 0 0 1px rgba(28,27,27,.12)}
        .empty-state{padding:56px 24px;text-align:center;background:var(--surface);box-shadow:var(--shadow-soft)}.empty-state h3{font-size:24px;margin-bottom:10px}.empty-state p{margin:0;color:var(--muted);line-height:1.8}
        .lightbox-slider{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;width:min(92vw,720px);max-height:82vh;border-radius:18px;scrollbar-width:none;-ms-overflow-style:none;-webkit-overflow-scrolling:touch;touch-action:pan-x pinch-zoom;background:var(--surface)}.lightbox-slider::-webkit-scrollbar{display:none}.lightbox-slide{min-width:100%;width:100%;scroll-snap-align:center;display:flex;align-items:center;justify-content:center;background:var(--surface)}.lightbox-slide img{width:100%;max-height:82vh;object-fit:contain;display:block;border-radius:18px;user-select:none;-webkit-user-drag:none}
        .hide-scrollbar{-ms-overflow-style:none;scrollbar-width:none}.hide-scrollbar::-webkit-scrollbar{display:none}
        @media (max-width:1180px){.container{width:min(100% - 48px,1600px)}.product-shell{grid-template-columns:1fr}.summary-card{position:relative;top:0}.related-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.gallery-slide{min-height:620px}}
        @media (max-width:760px){.container{width:100%}.main{padding-top:0}.product-hero{padding:18px 0 48px}.gallery-slide{min-height:420px;padding:10px}.gallery-thumbs{grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:0 14px 14px}.visual-copy{padding:18px}.summary-card{padding:20px}.pd-bottom-actions{grid-template-columns:1fr}.related-grid{grid-template-columns:1fr}.section{padding:64px 0}}
    </style>
    <style>
        .product-ui{padding:24px 0 72px;background:var(--surface)}
        .product-breadcrumbs{margin:0 0 18px;font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:rgba(28,27,27,.48);line-height:1.6}
        .product-showcase{display:grid;grid-template-columns:minmax(0,1.34fr) minmax(360px,.76fr);gap:32px;align-items:start}
        .gallery-rail{display:grid;grid-template-columns:92px minmax(0,1fr);gap:16px;align-items:start;min-width:0}
        .gallery-rail.single-image{grid-template-columns:minmax(0,1fr)}
        .gallery-thumb-rail{display:flex;flex-direction:column;gap:12px;position:sticky;top:108px;max-height:calc(100vh - 136px);overflow:auto;padding-right:4px}
        .gallery-thumb-btn{width:100%;border:1px solid rgba(28,27,27,.12);background:var(--surface);padding:0;overflow:hidden;cursor:pointer;aspect-ratio:3/4;box-shadow:var(--shadow-soft);transition:border-color .2s ease,transform .2s ease,box-shadow .2s ease}
        .gallery-thumb-btn:hover{transform:translateY(-1px)}
        .gallery-thumb-btn.active{border-color:var(--ink);box-shadow:0 0 0 2px rgba(28,27,27,.08)}
        .gallery-thumb-btn picture,.gallery-thumb-btn img{display:block;width:100%;height:100%}
        .gallery-thumb-btn img{object-fit:contain;object-position:center}
        .gallery-main{position:relative;background:var(--surface);border:1px solid rgba(28,27,27,.12);box-shadow:var(--shadow-soft);overflow:hidden;aspect-ratio:1 / 1;border-radius:0 !important;max-width:760px;margin:0 auto}
        .gallery-slider{height:100%;border-radius:0 !important;background:var(--surface) !important;min-height:0 !important}
        .gallery-slide{min-width:100%;scroll-snap-align:start;padding:0;background:var(--surface);display:flex;align-items:center;justify-content:center;height:100%}
        .gallery-slide picture,.gallery-slide img{display:block;width:100%;height:100%;max-width:100%;max-height:100%}
        .gallery-slide picture{border-radius:0 !important;overflow:visible !important}
        .gallery-slide img{aspect-ratio:auto !important;object-fit:contain !important;object-position:center !important;border-radius:0 !important;width:100% !important;height:100% !important;max-height:100% !important}
        .gallery-open-btn{all:unset;display:flex;align-items:center;justify-content:center;width:100%;height:100%;cursor:zoom-in}
        .gallery-open-btn picture,.gallery-open-btn img{display:block;width:100%;height:100%;max-width:100%;max-height:100%}
        .gallery-open-btn picture{border-radius:0 !important;overflow:visible !important}
        .gallery-nav{position:absolute;bottom:18px;z-index:3;width:40px;height:40px;border:0;background:var(--surface);color:var(--ink);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 18px rgba(0,0,0,.08);cursor:pointer}
        .gallery-nav.prev{left:18px}.gallery-nav.next{right:18px}
        .product-summary{position:sticky;top:108px;display:grid;gap:18px;padding:0 4px 0 0}
        .summary-kicker{display:inline-flex;align-items:center;gap:8px;padding:7px 10px;background:var(--surface);border:1px solid rgba(28,27,27,.12);font-size:10px;letter-spacing:.2em;text-transform:uppercase;width:fit-content}
        .summary-chips{display:flex;flex-wrap:wrap;gap:8px}
        .summary-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid rgba(28,27,27,.12);background:var(--surface);font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:rgba(28,27,27,.72);transition:border-color .2s ease, transform .2s ease, box-shadow .2s ease}
        .summary-chip:hover{border-color:var(--accent-red, var(--primary));transform:translateY(-1px);box-shadow:0 8px 18px rgba(31,31,31,.06)}
        .summary-title{font-size:clamp(28px,2.8vw,40px);line-height:1.08;letter-spacing:.02em;text-transform:uppercase;margin:0}
        .summary-price-stack{display:grid;gap:10px}
        .summary-prices{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap}
        .summary-sale-price{font-size:28px;font-weight:800;color:var(--accent-red, var(--primary))}
        .summary-old-price{font-size:14px;color:rgba(28,27,27,.42);text-decoration:line-through;font-weight:600}
        .summary-subline{margin-top:2px;font-size:13px;line-height:1.7;color:var(--muted)}
        .summary-installment{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface);border:1px solid rgba(28,27,27,.12);color:#166534;font-size:13px;font-weight:800;line-height:1.2;width:fit-content}
        .summary-installment .koko-installment-logo{height:18px;width:auto;flex-shrink:0;display:block}
        .summary-panel{display:grid;gap:18px;padding-top:4px}
        .summary-section{display:grid;gap:10px}
        .summary-label{font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:rgba(28,27,27,.6)}
        .variant-pills{display:flex;flex-wrap:wrap;gap:8px}
        .variant-pill{padding:9px 12px;border:1px solid rgba(28,27,27,.14);background:var(--surface);font-size:11px;letter-spacing:.16em;text-transform:uppercase;cursor:pointer;-webkit-appearance:none;appearance:none;font:inherit;line-height:1.2}
        .variant-pill.active{background:var(--accent-red, var(--primary));color:#fff;border-color:var(--accent-red, var(--primary))}
        .product-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%) translateY(18px);min-width:240px;max-width:min(92vw,420px);padding:14px 18px;border-radius:0;background:var(--ink);color:#fff;box-shadow:var(--shadow-soft);opacity:0;pointer-events:none;transition:opacity .2s ease,transform .2s ease;z-index:10020;display:flex;align-items:center;justify-content:space-between;gap:14px}
        .product-toast.show{opacity:1;transform:translateX(-50%) translateY(0);pointer-events:auto}
        .product-toast.success{background:var(--btn-cart-whatsapp-bg, #17663b)}
        .product-toast.error{background:#a43838}
        .product-toast .toast-text{font-size:13px;line-height:1.5;font-weight:600;letter-spacing:.02em}
        .cart-confirm-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.58);z-index:10030}
        .cart-confirm{width:min(92vw,420px);background:var(--surface);border:1px solid rgba(31,31,31,.10);box-shadow:none;padding:24px 22px 22px;display:grid;gap:18px}
        .cart-confirm-head{display:grid;gap:8px;justify-items:center;text-align:center}
        .cart-confirm-head h3{margin:0;font-size:24px;line-height:1.1;font-weight:900;color:#111;letter-spacing:-.03em}
        .cart-confirm-head p{margin:0;color:#777;font-size:13px;line-height:1.7}
        .cart-confirm-actions{display:flex;justify-content:center}
        .cart-confirm-actions button{min-width:128px;min-height:46px;border:1px solid var(--ink);background:var(--ink);color:#fff;font-size:12px;font-weight:900;letter-spacing:.14em;text-transform:uppercase;cursor:pointer}
        #orderModal .modal{width:min(92vw,610px);max-height:90vh;overflow-y:auto;background:var(--surface);border:1px solid rgba(31,31,31,.10);box-shadow:none;padding:24px 24px 22px;border-radius:0}
        #orderModal .modal-head{position:relative;display:grid;gap:6px;justify-items:center;text-align:center;margin-bottom:18px;padding-right:42px}
        #orderModal .modal-head h3{margin:0;font-size:28px;line-height:1.05;font-weight:900;color:#111;letter-spacing:-.03em}
        #orderModal .modal-head p{margin:0;max-width:430px;color:#777;font-size:13px;line-height:1.6}
        #orderModal .modal-close{position:absolute;right:0;top:0;width:38px;height:38px;border:1px solid rgba(31,31,31,.14);background:var(--surface);color:#111;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
        #orderModal .modal-form{display:grid;gap:12px}
        #orderModal .input-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        #orderModal .field label{display:block;margin-bottom:5px;font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#7c7777}
        #orderModal .field input,#orderModal .field textarea,#orderModal .field select{width:100%;min-height:46px;padding:10px 12px;border:1px solid rgba(31,31,31,.18);background:var(--surface);color:#111;font:inherit;box-shadow:inset 0 1px 0 rgba(255,255,255,.7);border-radius:0}
        #orderModal .field textarea{min-height:90px;resize:vertical}
        #orderModal .field input:focus,#orderModal .field textarea:focus,#orderModal .field select:focus{outline:none;border-color:rgba(185,0,11,.36);box-shadow:0 0 0 3px rgba(185,0,11,.08)}
        #orderModal .totals-box{background:#fafafa;border:1px solid rgba(31,31,31,.08);padding:16px 18px;display:grid;gap:10px;margin-top:4px}
        #orderModal .totals-row{display:flex;justify-content:space-between;gap:12px;font-size:13px}
        #orderModal .totals-row span{color:#7c7777}
        #orderModal .totals-row strong{font-size:14px;color:#111}
        #orderModal .modal-actions{display:flex;gap:10px;margin-top:2px}
        #orderModal .modal-actions button{flex:1;min-height:48px;border:1px solid rgba(31,31,31,.14);background:#f7f7f7;color:#111;font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;cursor:pointer}
        #orderModal .modal-actions .primary,#orderModal .modal-actions #orderSubmitButton{background:var(--btn-ordernow-bg, var(--primary)) !important;border-color:var(--btn-ordernow-bg, var(--primary)) !important;color:var(--btn-ordernow-text, #fff) !important;box-shadow:none}
        #orderModal .bank-details-box{display:none;background:#f4f8ff;border:1px solid #d8e4ff;padding:14px}
        #orderModal .bank-details-box strong{display:block;font-size:13px;font-weight:900;color:#123b7a;margin-bottom:6px}
        #orderModal .bank-details-box .text{font-size:12px;color:#345;line-height:1.7;white-space:pre-wrap}
        .qty-row{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
        .qty-row .qty-box{height:44px}
        .summary-actions{display:flex;align-items:stretch;gap:14px}
        .summary-actions .btn-action{flex:1 1 0;min-width:0;width:100%}
        .btn-action{min-height:54px;border:1px solid var(--ink);border-radius:0;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 18px;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,opacity .2s ease}
        .btn-action:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
        .btn-action:hover{transform:translateY(-1px)}
        .btn-buy-now{background:var(--btn-ordernow-bg, var(--primary));color:var(--btn-ordernow-text, #fff);box-shadow:0 12px 24px rgba(185,0,11,.16)}
        .btn-add-cart{background:var(--btn-addcart-bg, var(--ink));color:var(--btn-addcart-text, #fff);box-shadow:0 12px 24px rgba(28,27,27,.14)}
        .summary-stock-row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .summary-stock-badge{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid rgba(28,27,27,.12);background:var(--surface);font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:800}
        .summary-stock-badge i{font-size:8px}
        .summary-stock-in_stock{color:color-mix(in srgb, var(--btn-cart-whatsapp-bg, #17663b) 88%, #000);border-color:rgba(23,102,59,.16);background:color-mix(in srgb, var(--btn-cart-whatsapp-bg, #17663b) 10%, #ffffff)}
        .summary-stock-low_stock{color:#9a6400;border-color:rgba(154,100,0,.16);background:color-mix(in srgb, #ff9f43 10%, var(--surface))}
        .summary-stock-out_of_stock{color:#a43838;border-color:rgba(164,56,56,.16);background:color-mix(in srgb, #a43838 10%, var(--surface))}
        .summary-payment-logos{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
        .summary-payment-logo{display:inline-flex;align-items:center;justify-content:center;width:82px;height:50px;padding:8px 10px;border:1px solid rgba(28,27,27,.12);background:var(--surface);box-shadow:0 6px 16px rgba(28,27,27,.05)}
        .summary-payment-logo img{max-width:100%;max-height:100%;object-fit:contain}
        .summary-long-description{display:grid;gap:12px;padding:18px 0 0;margin-top:4px;border-top:1px solid rgba(28,27,27,.08)}
        .summary-long-description h3{font-size:18px;letter-spacing:-.02em;text-transform:uppercase}
        .summary-long-description .text{color:var(--muted);line-height:1.85;font-size:14px}
        .product-details-panel{margin-top:28px;border:1px solid rgba(28,27,27,.12);background:var(--surface);box-shadow:var(--shadow-soft);padding:22px 24px}
        .product-details-panel h3{margin:0 0 10px;font-size:22px;letter-spacing:-.02em;text-transform:uppercase}
        .product-details-panel .text{color:var(--muted);line-height:1.85;font-size:14px}
        .order-flash{margin:0 0 16px;padding:14px 16px;border:1px solid rgba(185,0,11,.18);background:color-mix(in srgb, var(--accent-red, var(--primary)) 8%, #ffffff);color:#8d0c14;font-size:13px;line-height:1.7}
        .order-flash strong{display:block;font-size:11px;letter-spacing:.18em;text-transform:uppercase;margin-bottom:4px}
        .related-section{padding-top:82px}
        .related-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:26px}
        .related-heading h2{font-size:clamp(28px,3vw,44px);letter-spacing:-.03em;text-transform:uppercase}
        .related-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:28px 22px}
        .related-card{display:grid;gap:12px}
        .related-media{display:block;aspect-ratio:3/4;overflow:hidden;background:var(--surface);border:1px solid rgba(28,27,27,.12);box-shadow:var(--shadow-soft)}
        .related-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease}
        .related-card:hover .related-media img{transform:scale(1.03)}
        .related-title{margin:0;font-size:14px;line-height:1.35;letter-spacing:.02em;text-transform:uppercase}
        .related-price{font-size:13px;font-weight:800;color:var(--ink)}
        .related-old-price{font-size:12px;color:rgba(28,27,27,.42);text-decoration:line-through}
        .related-card .product-btn{display:none}
        .gallery-dots{display:none}
        @media (max-width:1180px){.product-showcase{grid-template-columns:1fr}.gallery-rail{grid-template-columns:82px minmax(0,1fr)}.product-summary{position:relative;top:0;padding:0}.related-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:760px){.product-ui{padding:18px 0 64px}.gallery-rail{grid-template-columns:1fr;gap:10px}.gallery-thumb-rail{position:relative;top:0;flex-direction:row;gap:8px;max-height:none;overflow-x:auto;overflow-y:hidden;padding-right:0;padding-bottom:4px}.gallery-thumb-btn{min-width:72px;max-width:72px}.gallery-main{aspect-ratio:auto !important;overflow:visible;border:1px solid rgba(28,27,27,.12);box-shadow:var(--shadow-soft);background:var(--surface);border-radius:0 !important}.gallery-slider{height:auto !important;border-radius:0 !important;background:var(--surface) !important}.gallery-slide{padding:0;align-items:flex-start;justify-content:flex-start;min-height:0;background:var(--surface);height:auto}.gallery-open-btn{justify-content:flex-start;align-items:flex-start;height:auto}.gallery-slide picture,.gallery-slide img,.gallery-open-btn picture,.gallery-open-btn img{width:100% !important;max-width:100%;height:auto !important;max-height:none !important;object-fit:contain !important;aspect-ratio:auto !important;border-radius:0 !important}.gallery-slide picture,.gallery-open-btn picture{overflow:visible !important}.gallery-nav{width:36px;height:36px}.summary-actions{display:grid;grid-template-columns:1fr}.summary-payment-logo{width:72px;height:44px;padding:7px 8px}.summary-title{font-size:24px}.summary-sale-price{font-size:24px}.related-heading{align-items:flex-start;flex-direction:column}.related-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 14px}.related-title{font-size:12px}#orderModal .modal{padding:18px}#orderModal .modal-head{padding-right:0}#orderModal .modal-close{position:static;justify-self:center;margin-top:6px}#orderModal .modal-actions{flex-direction:column}#orderModal .input-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="page">
    <?php require_once 'views/layouts/customer_layout.php'; customer_layout_start(); ?>

    <main class="main product-ui">
        <section class="product-hero">
            <div class="container">
        <div class="product-breadcrumbs">
            HOME / SHOP
            <?php if (!empty($currentCategoryLink)): ?>
                / <a href="<?= htmlspecialchars($baseUrl . 'shop/category/' . $currentCategoryLink['id']) ?>"><?= htmlspecialchars(strtoupper($currentCategoryLink['name'])) ?></a>
            <?php endif; ?>
            / <?= htmlspecialchars($product['title'] ?? 'PRODUCT') ?>
        </div>
            <?php if (!empty($_SESSION['order_error'])): ?>
                <div class="order-flash">
                    <strong>Checkout notice</strong>
                    <?= htmlspecialchars((string) $_SESSION['order_error']) ?>
                </div>
                <?php unset($_SESSION['order_error']); ?>
            <?php endif; ?>

                <div class="product-showcase">
                    <div class="gallery-rail<?= $hasGalleryCarousel ? '' : ' single-image' ?>">
                        <?php if ($hasGalleryCarousel): ?>
                            <div class="gallery-thumb-rail" aria-label="Gallery thumbnails">
                                <?php foreach ($gallerySlides as $slideIndex => $slide): ?>
                                    <button type="button" class="gallery-thumb-btn <?= $slideIndex === 0 ? 'active' : '' ?>" data-gallery-thumb="<?= (int) $slideIndex ?>" onclick="scrollToGallery(<?= (int) $slideIndex ?>)" aria-label="Open gallery image <?= (int) $slideIndex + 1 ?>">
                                        <?= ImageHelper::renderResponsivePicture(
                                            $slide['path'] ?? '',
                                            $slide['url'] ?? '',
                                            [
                                                'alt' => $slide['alt'] ?? 'Product image',
                                                'loading' => 'lazy',
                                                'decoding' => 'async',
                                                'fetchpriority' => 'low'
                                            ],
                                            'product_card'
                                        ) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="gallery-main" style="border-radius:0 !important; overflow:hidden !important;">
                            <?php if ($hasGalleryCarousel): ?>
                                <div class="gallery-slider" data-gallery-slider>
                                    <?php foreach ($gallerySlides as $slideIndex => $slide): ?>
                                        <div class="gallery-slide" style="border-radius:0 !important; overflow:hidden !important;">
                                            <button type="button" class="gallery-open-btn" onclick="openImageModal(<?= (int) $slideIndex ?>)" aria-label="Open gallery image <?= (int) $slideIndex + 1 ?>" style="border-radius:0 !important; overflow:hidden !important;">
                                                <?= ImageHelper::renderResponsivePicture(
                                                    $slide['path'] ?? '',
                                                    $slide['url'] ?? '',
                                                    [
                                                        'class' => 'gallery-img ' . ($slideIndex === 0 ? 'current' : ''),
                                                        'alt' => $slide['alt'] ?? 'Product image',
                                                        'loading' => $slideIndex === 0 ? 'eager' : 'lazy',
                                                        'decoding' => $slideIndex === 0 ? 'sync' : 'async',
                                                        'fetchpriority' => $slideIndex === 0 ? 'high' : 'low',
                                                        'data-index' => (string) $slideIndex,
                                                        'style' => 'border-radius:0 !important; object-fit:contain !important; object-position:center !important; aspect-ratio:auto !important; width:100% !important; height:100% !important; max-width:100% !important; max-height:100% !important; display:block !important; clip-path:inset(0) !important;'
                                                    ],
                                                    'product_gallery'
                                                ) ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="gallery-nav prev" data-gallery-prev aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
                                <button type="button" class="gallery-nav next" data-gallery-next aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
                            <?php else: ?>
                                <?php $primarySlide = $gallerySlides[0] ?? ['path' => '', 'url' => $mainImageUrl, 'alt' => (string) ($product['title'] ?? 'Product image')]; ?>
                                <div class="gallery-slide" style="border-radius:0 !important; overflow:hidden !important;">
                                    <button type="button" class="gallery-open-btn" onclick="openImageModal(0)" aria-label="Open product image" style="border-radius:0 !important; overflow:hidden !important;">
                                        <?= ImageHelper::renderResponsivePicture(
                                            $primarySlide['path'] ?? '',
                                            $primarySlide['url'] ?? '',
                                            [
                                                'class' => 'gallery-img current',
                                                'alt' => $primarySlide['alt'] ?? 'Product image',
                                                'loading' => 'eager',
                                                'decoding' => 'sync',
                                                'fetchpriority' => 'high',
                                                'data-index' => '0',
                                                'style' => 'border-radius:0 !important; object-fit:contain !important; object-position:center !important; aspect-ratio:auto !important; width:100% !important; height:100% !important; max-width:100% !important; max-height:100% !important; display:block !important; clip-path:inset(0) !important;'
                                            ],
                                            'product_gallery'
                                        ) ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="product-summary">
                        <div class="summary-chips">
                            <?php if (!empty($parentCategoryLink) && !empty($parentCategoryLink['name'])): ?><a class="summary-chip" href="<?= htmlspecialchars($baseUrl . 'shop/category/' . $parentCategoryLink['id']) ?>"><?= htmlspecialchars($parentCategoryLink['name']) ?></a><?php endif; ?>
                            <?php if (!empty($currentCategoryLink) && !empty($currentCategoryLink['name'])): ?><a class="summary-chip" href="<?= htmlspecialchars($baseUrl . 'shop/category/' . $currentCategoryLink['id']) ?>"><?= htmlspecialchars($currentCategoryLink['name']) ?></a><?php endif; ?>
                            <?php if (!empty($product['free_shipping'])): ?><span class="summary-chip">Free Shipping</span><?php endif; ?>
                        </div>

                        <h1 class="summary-title"><?= htmlspecialchars($product['title'] ?? 'Product') ?></h1>

                        <div class="summary-price-stack">
                            <div class="summary-prices">
                                <span class="summary-sale-price" id="productCurrentPrice"><?= htmlspecialchars($currency) ?> <?= number_format($productSalePrice !== null ? $productSalePrice : $productRegularPrice, 0) ?></span>
                                <span class="summary-old-price" id="productOldPrice" style="<?= $productSalePrice !== null ? '' : 'display:none;' ?>"><?= htmlspecialchars($currency) ?> <?= number_format($productRegularPrice, 0) ?></span>
                            </div>

                            <?php if (!empty($kokoTeaserData)): ?>
                                <div class="summary-installment koko-installment-teaser koko-installment-teaser-single" id="productKokoPlan" aria-label="KOKO installment plan">
                                    <span class="koko-installment-text" id="productKokoPlanText">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaserData['installment_amount'], 2) ?></span>
                                    <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="koko-installment-logo">
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if ($shortDescription !== ''): ?>
                            <div class="summary-subline"><?= htmlspecialchars($shortDescription) ?></div>
                        <?php endif; ?>

                        <div class="summary-panel">
                            <?php if (!empty($variations)): ?>
                                <?php foreach ($variations as $varName => $values): ?>
                                    <div class="summary-section">
                                        <div class="summary-label"><?= htmlspecialchars(strtoupper((string) $varName)) ?></div>
                                        <div class="variant-pills">
                                            <?php foreach ($values as $val): ?>
                                                <button type="button" class="variant-pill" role="button" tabindex="0" data-variation-id="<?= (int) ($val['variation_id'] ?? 0) ?>" data-variation-name="<?= htmlspecialchars($varName, ENT_QUOTES) ?>" data-value-id="<?= (int) $val['id'] ?>" data-value-label="<?= htmlspecialchars($val['value'], ENT_QUOTES) ?>" aria-pressed="false"><?= htmlspecialchars($val['value']) ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="stock-filter-actions"><button type="button" id="clearStockFilterBtn" class="stock-clear-btn" onclick="clearVariationSelection()" style="display:none;">Clear Filter</button></div>
                            <?php endif; ?>

                            <div class="summary-section">
                                <div class="summary-label">Stock Status</div>
                                <div class="summary-stock-row">
                                    <div id="productStockNotice" class="summary-stock-badge summary-stock-<?= htmlspecialchars($stockStatus) ?>" style="display:inline-flex;">
                                        <i class="fas fa-circle"></i>
                                        <span><?= htmlspecialchars($stockStatusLabel) ?></span>
                                        <?php if ($stockStatusQty !== null): ?>
                                            <span><?= (int) $stockStatusQty ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="qty-row">
                                <span style="font-weight:600;font-size:14px;color:#000;">Quantity:</span>
                                <div class="qty-box">
                                    <button type="button" onclick="updateQty(-1)">-</button>
                                    <input type="number" id="qtyInput" value="1" min="1" readonly>
                                    <button type="button" onclick="updateQty(1)">+</button>
                                </div>
                            <?php if ($hasSizeGuide): ?>
                                <div class="summary-inline-action" style="display:inline-flex;">
                                    <button class="btn-size-guide" type="button" onclick="openSizeGuide()" style="display:inline-flex;align-items:center;gap:8px;justify-content:center;height:42px;min-height:42px;padding:0 14px;border:1px solid #1f1f1f !important;background:var(--surface) !important;color:#1f1f1f !important;border-radius:0 !important;box-shadow:none !important;font-weight:800;letter-spacing:.18em;text-transform:uppercase;font-size:10px;white-space:nowrap;appearance:none;-webkit-appearance:none;">
                                        <i class="fas fa-ruler-combined" style="font-size:14px;line-height:1;"></i>
                                        <span>Size Guide</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            </div>

                            <div class="summary-actions">
                                <button class="btn-action btn-add-cart" type="button" data-purchase-action="add-cart" onclick="addToCartFromProductPage()" <?= $initialPurchaseDisabled ? 'disabled aria-disabled="true"' : '' ?>><i class="fas fa-cart-plus"></i><span>Add to Cart</span></button>
                                <button class="btn-action btn-buy-now" type="button" data-purchase-action="buy-now" onclick="openPaymentMethodSheet()" <?= $initialPurchaseDisabled ? 'disabled aria-disabled="true"' : '' ?>><i class="fas fa-bag-shopping"></i><span>Buy It Now</span></button>
                            </div>

                            <?php if (!empty($paymentModeLogos)): ?>
                                <div class="summary-section">
                                    <div class="summary-label">Allowed Payment Modes</div>
                                    <div class="summary-payment-logos">
                                        <?php foreach ($paymentModeLogos as $modeLogo): ?>
                                            <span class="summary-payment-logo" title="<?= htmlspecialchars($modeLogo['label']) ?>">
                                                <img src="<?= htmlspecialchars($modeLogo['src']) ?>" alt="<?= htmlspecialchars($modeLogo['alt']) ?>">
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </aside>
                </div>

                <?php if ($longDescription !== ''): ?>
                    <section class="product-details-panel" aria-label="Product Details">
                        <h3>Product Details</h3>
                        <div class="text"><?= nl2br(htmlspecialchars($longDescription)) ?></div>
                    </section>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($relatedProducts)): ?>
            <section class="section related-section">
                <div class="container">
                    <div class="related-heading">
                        <h2>You May Also Like</h2>
                        <a class="section-link" href="<?= htmlspecialchars($baseUrl . 'shop') ?>">Shop More</a>
                    </div>
                    <div class="related-grid">
                        <?php foreach ($relatedProducts as $prod): ?>
                            <?php
                            $prodImage = ImageHelper::uploadUrl(
                                $prod['main_image'] ?? '',
                                'https://via.placeholder.com/720x960?text=' . urlencode($prod['title'] ?? 'Product')
                            );
                            $relatedRegular = (float) ($prod['price'] ?? 0);
                            $relatedSale = (!empty($prod['sale_price']) && (float) $prod['sale_price'] < $relatedRegular) ? (float) $prod['sale_price'] : null;
                            ?>
                            <article class="related-card">
                                <a class="related-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $prod['id']) ?>">
                                    <?= ImageHelper::renderResponsivePicture(
                                        $prod['main_image'] ?? '',
                                        $prodImage,
                                        [
                                            'alt' => $prod['title'] ?? 'Product',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'product_card'
                                    ) ?>
                                </a>
                                <h3 class="related-title"><?= htmlspecialchars($prod['title'] ?? 'Product') ?></h3>
                                <div class="related-price">
                                    <?php if ($relatedSale !== null): ?>
                                        <?= htmlspecialchars($currency) ?> <?= number_format($relatedSale, 0) ?> <span class="related-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($relatedRegular, 0) ?></span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($currency) ?> <?= number_format($relatedRegular, 0) ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php customer_layout_end(); ?>
    </main>
</div>

<?php if ($hasSizeGuide): ?>
<div id="sgModal" class="modal-overlay size-guide-lightbox" onclick="closeSizeGuide()" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:3050;background:rgba(0,0,0,.72);">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="size-guide-close" onclick="closeSizeGuide()"><i class="fas fa-times" aria-hidden="true"></i></div>
        <?= ImageHelper::renderResponsivePicture($product['size_guide_image'] ?? '', $sizeGuideImage, ['alt' => 'Size guide','loading' => 'lazy','decoding' => 'async','fetchpriority' => 'low','style' => 'width:100%;display:block;border-radius:0 !important;object-fit:contain !important;'], 'product_gallery') ?>
    </div>
</div>
<?php endif; ?>

<div id="imgModal" class="modal-overlay" onclick="closeImageModal()" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:3000;background:rgba(0,0,0,.72);">
    <div onclick="event.stopPropagation()" style="position:relative;display:inline-block;width:min(92vw,720px);">
        <div onclick="closeImageModal()" style="position:absolute;top:-15px;right:-15px;cursor:pointer;z-index:3001;background:white;border-radius:50%;width:35px;height:35px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,0.2);border:1px solid #eee;"><i class="fas fa-times" style="color:#111;font-size:16px;"></i></div>
        <button type="button" onclick="moveImageModal(-1)" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);z-index:3001;width:38px;height:38px;border:none;border-radius:50%;background:rgba(255,255,255,0.92);box-shadow:0 2px 10px rgba(0,0,0,0.18);cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chevron-left" style="color:#111;font-size:14px;"></i></button>
        <div id="imgModalSlider" class="lightbox-slider"></div>
        <button type="button" onclick="moveImageModal(1)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);z-index:3001;width:38px;height:38px;border:none;border-radius:50%;background:rgba(255,255,255,0.92);box-shadow:0 2px 10px rgba(0,0,0,0.18);cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chevron-right" style="color:#111;font-size:14px;"></i></button>
    </div>
</div>

<div id="paymentMethodSheet" class="payment-sheet-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:3100;align-items:flex-end;justify-content:center;" onclick="closePaymentMethodSheet()">
    <div class="payment-sheet" onclick="event.stopPropagation()" style="width:min(100%,720px);background:var(--surface);border-radius:24px 24px 0 0;padding:18px 18px 24px;box-shadow:0 -20px 40px rgba(0,0,0,.18);">
        <div style="width:54px;height:5px;border-radius:999px;background:#e7e3e1;margin:0 auto 16px;"></div>
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:16px;">
            <div><div style="font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#a0a0a0;margin-bottom:6px;">Choose Payment Method</div><h3 style="margin:0;font-size:22px;">Select how you want to order</h3></div>
            <button type="button" onclick="closePaymentMethodSheet()" style="border:0;background:transparent;font-size:20px;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div style="display:grid;gap:12px;">
            <?php if ($whatsappEnabled): ?><button type="button" class="payment-method-card" onclick="choosePaymentMethod('whatsapp')" style="display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #eee;border-radius:18px;background:linear-gradient(180deg,color-mix(in srgb, var(--btn-cart-whatsapp-bg, #25d366) 12%, var(--surface)) 0%, var(--surface) 100%);color:var(--btn-cart-whatsapp-bg, #25d366);cursor:pointer;text-align:left;"><span style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--btn-cart-whatsapp-bg, #25d366) 18%, #ffffff);color:inherit;font-size:18px;"><i class="fab fa-whatsapp"></i></span><span style="flex:1;"><strong style="display:block;font-size:16px;margin-bottom:4px;color:inherit;">WhatsApp Order</strong><small style="color:color-mix(in srgb, var(--btn-cart-whatsapp-bg, #25d366) 78%, transparent);line-height:1.5;">Send your order details directly to the shop on WhatsApp.</small></span><i class="fas fa-chevron-right" style="color:currentColor;"></i></button><?php endif; ?>
            <?php if ($codEnabled): ?><button type="button" class="payment-method-card" onclick="choosePaymentMethod('cod')" style="display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #eee;border-radius:18px;background:linear-gradient(180deg,color-mix(in srgb, var(--btn-cart-cod-bg, #111111) 10%, var(--surface)) 0%, var(--surface) 100%);color:var(--btn-cart-cod-bg, #111111);cursor:pointer;text-align:left;"><span style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--btn-cart-cod-bg, #111111) 16%, #ffffff);color:inherit;font-size:18px;"><i class="fas fa-hand-holding-dollar"></i></span><span style="flex:1;"><strong style="display:block;font-size:16px;margin-bottom:4px;color:inherit;">Cash on Delivery</strong><small style="color:color-mix(in srgb, var(--btn-cart-cod-bg, #111111) 78%, transparent);line-height:1.5;">Place the order now and pay when it is delivered.</small></span><i class="fas fa-chevron-right" style="color:currentColor;"></i></button><?php endif; ?>
            <?php if ($payhereReady): ?><button type="button" class="payment-method-card" onclick="choosePaymentMethod('payhere')" style="display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #eee;border-radius:18px;background:linear-gradient(180deg,color-mix(in srgb, var(--btn-cart-payhere-bg, #111111) 10%, var(--surface)) 0%, var(--surface) 100%);color:var(--btn-cart-payhere-bg, #111111);cursor:pointer;text-align:left;"><span style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--btn-cart-payhere-bg, #111111) 16%, #ffffff);color:inherit;font-size:18px;"><i class="fas fa-credit-card"></i></span><span style="flex:1;"><strong style="display:block;font-size:16px;margin-bottom:4px;color:inherit;">Card Payments</strong><small style="color:color-mix(in srgb, var(--btn-cart-payhere-bg, #111111) 78%, transparent);line-height:1.5;">Pay online securely before your order is confirmed.</small></span><i class="fas fa-chevron-right" style="color:currentColor;"></i></button><?php endif; ?>
            <?php if ($kokoReady): ?><button type="button" class="payment-method-card" onclick="choosePaymentMethod('koko')" style="display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #eee;border-radius:18px;background:linear-gradient(180deg,color-mix(in srgb, var(--btn-cart-koko-bg, #fff3dc) 12%, var(--surface)) 0%, var(--surface) 100%);color:var(--btn-cart-koko-text, #111111);cursor:pointer;text-align:left;"><span style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--btn-cart-koko-bg, #fff3dc) 18%, #ffffff);color:inherit;font-size:18px;"><i class="fas fa-layer-group"></i></span><span style="flex:1;"><strong style="display:block;font-size:16px;margin-bottom:4px;color:inherit;">KOKO Payments</strong><small style="color:color-mix(in srgb, var(--btn-cart-koko-text, #111111) 78%, transparent);line-height:1.5;">Split your payment into 3 interest-free installments.</small></span><i class="fas fa-chevron-right" style="color:currentColor;"></i></button><?php endif; ?>
            <?php if ($bankTransferEnabled): ?><button type="button" class="payment-method-card" onclick="choosePaymentMethod('bank_transfer')" style="display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #eee;border-radius:18px;background:linear-gradient(180deg,color-mix(in srgb, #7b4d1a 12%, var(--surface)) 0%, var(--surface) 100%);cursor:pointer;text-align:left;"><span style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, #7b4d1a 16%, #ffffff);color:#7b4d1a;font-size:18px;"><i class="fas fa-building-columns"></i></span><span style="flex:1;"><strong style="display:block;font-size:16px;margin-bottom:4px;">Bank Transfer</strong><small style="color:#666;line-height:1.5;">Place the order now and send the payment using the bank details provided.</small></span><i class="fas fa-chevron-right" style="color:#999;"></i></button><?php endif; ?>
        </div>
    </div>
</div>

<div id="cartConfirmOverlay" class="cart-confirm-overlay" onclick="closeCartConfirm()">
    <div class="cart-confirm" onclick="event.stopPropagation()">
        <div class="cart-confirm-head">
            <h3 id="cartConfirmTitle">Added to basket</h3>
            <p id="cartConfirmText">The item was added to your cart successfully.</p>
        </div>
        <div class="cart-confirm-actions">
            <button type="button" onclick="closeCartConfirm()">OK</button>
        </div>
    </div>
</div>

<div id="orderModal" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.58);z-index:3200;align-items:center;justify-content:center;padding:18px;">
    <div class="modal">
        <div class="modal-head">
            <div>
                <h3 id="orderModalTitle">Complete Your Order</h3>
                <p>Fill the customer information and place the product order.</p>
            </div>
            <button type="button" class="modal-close" onclick="closeOrderModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form onsubmit="event.preventDefault(); submitOrder();" class="modal-form">
            <div class="input-row">
                <div class="field"><label for="ordName">Full Name</label><input type="text" id="ordName" placeholder="Full Name *" required></div>
                <div class="field"><label for="ordEmail">Email Address</label><input type="email" id="ordEmail" placeholder="Email Address *" required></div>
            </div>
            <div class="field"><label for="ordAddress">Address</label><textarea id="ordAddress" placeholder="Address *" required></textarea></div>
            <div class="input-row">
                <div class="field"><label for="ordCity">City</label><input type="text" id="ordCity" placeholder="City *" required></div>
                <div class="field"><label for="ordDistrict">District</label>
                    <select id="ordDistrict" required>
                        <option value="">Select district *</option>
                        <?php foreach (($deliveryDistricts ?? []) as $districtName): ?>
                            <option value="<?= htmlspecialchars($districtName) ?>"><?= htmlspecialchars($districtName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="input-row">
                <div class="field"><label for="ordPhone1">Phone Number 01</label><input type="tel" id="ordPhone1" placeholder="Phone Number 01 *" required></div>
                <div class="field"><label for="ordPhone2">Phone Number 02</label><input type="tel" id="ordPhone2" placeholder="Phone Number 02"></div>
            </div>
            <div class="field"><label for="ordNote">Special Note</label><textarea id="ordNote" placeholder="Special Note"></textarea></div>
            <?php if ($bankTransferEnabled && !empty($settings['bank_transfer_details'])): ?>
                <div id="bankTransferDetailsBox" class="bank-details-box">
                    <strong>Bank Transfer Details</strong>
                    <div class="text"><?= nl2br(htmlspecialchars($settings['bank_transfer_details'])) ?></div>
                </div>
            <?php endif; ?>
            <div class="totals-box">
                <div class="totals-row"><span>Subtotal</span><strong id="modalSubTotalDisplay"></strong></div>
                <div class="totals-row"><span>Shipping Fee</span><strong id="modalShippingDisplay">Select district</strong></div>
                <div id="modalHandlingFeeRow" class="totals-row" style="display:none;"><span>Handling Fee</span><strong id="modalHandlingFeeDisplay"></strong></div>
                <div class="totals-row"><span>Order Total</span><strong id="modalGrandTotalDisplay"></strong></div>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeOrderModal()">Cancel</button>
                <button type="submit" id="orderSubmitButton" class="primary">Send via WhatsApp</button>
            </div>
        </form>
    </div>
</div>

    <script>
    const productId = <?= (int) ($product['id'] ?? 0) ?>;
    const productTitle = <?= json_encode((string) ($product['title'] ?? 'Product')) ?>;
    const currencyCode = <?= json_encode((string) $currency) ?>;
    const baseProductPrice = <?= json_encode((float) $productUnitPrice) ?>;
    const baseProductRegularPrice = <?= json_encode((float) $productRegularPrice) ?>;
    const baseProductSalePrice = <?= json_encode($productSalePrice !== null ? (float) $productSalePrice : null) ?>;
    const kokoHandlingFeePercentage = <?= json_encode((float) ($settings['koko_handling_fee_percentage'] ?? 0)) ?>;
    const deliveryApplyAllDistricts = <?= json_encode($deliveryApplyAllDistricts) ?>;
    const deliveryAllFirstKg = <?= json_encode($deliveryAllFirstKg) ?>;
    const deliveryAllAdditionalKg = <?= json_encode($deliveryAllAdditionalKg) ?>;
    const deliveryRatesMap = <?= json_encode($deliveryRatesMap ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const variantStockRows = <?= json_encode($variant_stock_rows ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const defaultProductImageUrl = <?= json_encode($mainImageUrl) ?>;
    const shopWhatsappTarget = <?= json_encode($shopWhatsappTarget) ?>;
    const recaptchaCheckoutEnabled = <?= json_encode($recaptchaCheckoutEnabled) ?>;
    const recaptchaSiteKey = <?= json_encode($recaptchaSiteKey) ?>;
    const isFreeShipping = <?= json_encode(!empty($product['free_shipping'])) ?>;
    const initialStockStatus = <?= json_encode($stockStatus) ?>;
    const initialStockQty = <?= json_encode($stockStatusQty === null ? null : (int) $stockStatusQty) ?>;
    const customerProfileStorageKey = 'style1_customer_order_profile_v1';

    let selectedVariations = {};
    let orderMode = 'cod';
    let modalImageIndex = 0;
    let toastTimer = null;

    function formatMoney(value) {
        const amount = Math.max(0, Number(value || 0));
        return currencyCode + ' ' + new Intl.NumberFormat('en-LK', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(amount));
    }

    function formatKokoInstallment(value) {
        const amount = Math.max(0, Number(value || 0));
        return currencyCode + ' ' + amount.toFixed(2);
    }

    function showProductToast(message, type) {
        let toast = document.getElementById('productToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'productToast';
            toast.className = 'product-toast';
            toast.innerHTML = '<div class="toast-text"></div>';
            document.body.appendChild(toast);
        }
        toast.classList.remove('success', 'error', 'show');
        if (type === 'success') toast.classList.add('success');
        if (type === 'error') toast.classList.add('error');
        toast.querySelector('.toast-text').textContent = String(message || '');
        window.clearTimeout(toastTimer);
        toast.classList.add('show');
        toastTimer = window.setTimeout(function () { toast.classList.remove('show'); }, 2500);
    }

    function showCartConfirm(message) {
        const overlay = document.getElementById('cartConfirmOverlay');
        const text = document.getElementById('cartConfirmText');
        if (!overlay) return;
        if (text) text.textContent = message || 'The item was added to your cart successfully.';
        overlay.style.display = 'flex';
    }

    function closeCartConfirm() {
        const overlay = document.getElementById('cartConfirmOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    function getStoredCustomerProfile() {
        try {
            const raw = window.localStorage.getItem(customerProfileStorageKey);
            if (!raw) return {};
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function saveCustomerProfile(payload) {
        try {
            const normalized = {
                customer_name: String(payload.customer_name || '').trim(),
                email: String(payload.email || '').trim(),
                phone: String(payload.phone || '').trim(),
                phone_alt: String(payload.phone_alt || '').trim(),
                address: String(payload.address || '').trim(),
                city: String(payload.city || '').trim(),
                district: String(payload.district || '').trim(),
                note: String(payload.note || '').trim()
            };
            window.localStorage.setItem(customerProfileStorageKey, JSON.stringify(normalized));
        } catch (error) {}
    }

    function getRecaptchaToken() {
        return new Promise(function (resolve, reject) {
            if (!recaptchaCheckoutEnabled) {
                resolve('');
                return;
            }
            if (!recaptchaSiteKey) {
                reject(new Error('Checkout protection is not configured. Please contact the shop owner.'));
                return;
            }
            if (!window.grecaptcha || typeof window.grecaptcha.ready !== 'function' || typeof window.grecaptcha.execute !== 'function') {
                reject(new Error('Checkout protection is not ready yet. Please try again.'));
                return;
            }
            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(recaptchaSiteKey, { action: 'checkout_order' }).then(function (token) {
                    resolve(token || '');
                }).catch(function () {
                    reject(new Error('Unable to verify checkout right now. Please try again.'));
                });
            });
        });
    }

    function hydrateCustomerProfile() {
        const data = getStoredCustomerProfile();
        const ordName = document.getElementById('ordName');
        const ordEmail = document.getElementById('ordEmail');
        const ordAddress = document.getElementById('ordAddress');
        const ordCity = document.getElementById('ordCity');
        const ordDistrict = document.getElementById('ordDistrict');
        const ordPhone1 = document.getElementById('ordPhone1');
        const ordPhone2 = document.getElementById('ordPhone2');
        const ordNote = document.getElementById('ordNote');
        if (ordName && !ordName.value && data.customer_name) ordName.value = data.customer_name;
        if (ordEmail && !ordEmail.value && data.email) ordEmail.value = data.email;
        if (ordAddress && !ordAddress.value && data.address) ordAddress.value = data.address;
        if (ordCity && !ordCity.value && data.city) ordCity.value = data.city;
        if (ordDistrict && data.district && !ordDistrict.value) ordDistrict.value = data.district;
        if (ordPhone1 && !ordPhone1.value && data.phone) ordPhone1.value = data.phone;
        if (ordPhone2 && !ordPhone2.value && data.phone_alt) ordPhone2.value = data.phone_alt;
        if (ordNote && !ordNote.value && data.note) ordNote.value = data.note;
    }

    function captureCustomerProfile() {
        saveCustomerProfile({
            customer_name: document.getElementById('ordName') ? document.getElementById('ordName').value : '',
            email: document.getElementById('ordEmail') ? document.getElementById('ordEmail').value : '',
            phone: document.getElementById('ordPhone1') ? document.getElementById('ordPhone1').value : '',
            phone_alt: document.getElementById('ordPhone2') ? document.getElementById('ordPhone2').value : '',
            address: document.getElementById('ordAddress') ? document.getElementById('ordAddress').value : '',
            city: document.getElementById('ordCity') ? document.getElementById('ordCity').value : '',
            district: document.getElementById('ordDistrict') ? document.getElementById('ordDistrict').value : '',
            note: document.getElementById('ordNote') ? document.getElementById('ordNote').value : ''
        });
    }

    function getGalleryImages() {
        return Array.from(document.querySelectorAll('.gallery-main .gallery-img')).map(function (img) { return img.getAttribute('src'); }).filter(Boolean);
    }

    function syncGalleryUI(index) {
        document.querySelectorAll('.gallery-dots .dot').forEach(function (dot, dotIndex) {
            dot.classList.toggle('active', dotIndex === index);
        });
        document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb, thumbIndex) {
            thumb.classList.toggle('active', thumbIndex === index);
        });
    }

    function scrollToGallery(index, behavior) {
        const slider = document.querySelector('[data-gallery-slider]');
        if (!slider) return;
        const images = getGalleryImages();
        const targetIndex = Math.max(0, Math.min(Number(index || 0), Math.max(images.length - 1, 0)));
        slider.scrollTo({ left: slider.clientWidth * targetIndex, behavior: behavior || 'smooth' });
        syncGalleryUI(targetIndex);
    }

    function getCurrentGalleryIndex() {
        const activeThumb = document.querySelector('[data-gallery-thumb].active');
        if (activeThumb) {
            return Math.max(0, Number(activeThumb.getAttribute('data-gallery-thumb') || 0));
        }
        return 0;
    }

    function renderImageModalSlides() {
        const slider = document.getElementById('imgModalSlider');
        if (!slider) return;
        const images = getGalleryImages();
        slider.innerHTML = images.map(function (src, index) {
            return '<div class="lightbox-slide"><img src="' + src + '" alt="Product image ' + (index + 1) + '"></div>';
        }).join('');
    }

    function syncModalImagePosition(index, behavior) {
        const slider = document.getElementById('imgModalSlider');
        const images = getGalleryImages();
        if (!slider || !images.length) return;
        modalImageIndex = Math.max(0, Math.min(index, images.length - 1));
        slider.scrollTo({ left: slider.clientWidth * modalImageIndex, behavior: behavior || 'smooth' });
    }

    function openImageModal(index) {
        renderImageModalSlides();
        const images = getGalleryImages();
        if (!images.length) return;
        document.getElementById('imgModal').style.display = 'flex';
        syncModalImagePosition(Number(index || 0), 'auto');
    }

    function closeImageModal() {
        document.getElementById('imgModal').style.display = 'none';
    }

    function moveImageModal(direction) {
        syncModalImagePosition(modalImageIndex + direction, 'smooth');
    }

    function handleImageModalKeydown(event) {
        const modal = document.getElementById('imgModal');
        if (!modal || modal.style.display === 'none') return;
        if (event.key === 'Escape') {
            closeImageModal();
            return;
        }
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveImageModal(-1);
        }
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveImageModal(1);
        }
    }

    function openSizeGuide() {
        const modal = document.getElementById('sgModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeSizeGuide() {
        const modal = document.getElementById('sgModal');
        if (modal) modal.style.display = 'none';
    }

    function updateQty(change) {
        const qtyInput = document.getElementById('qtyInput');
        if (!qtyInput) return;
        qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) + change);
        updateOrderTotals();
    }

    function getSelectedVariantKey() {
        return Object.values(selectedVariations).map(function (item) { return item.variation_id + ':' + item.variation_value_id; }).sort().join('|');
    }

    function getVariantText() {
        return Object.values(selectedVariations).sort(function (a, b) { return String(a.variation_name).localeCompare(String(b.variation_name)); }).map(function (item) { return item.variation_name + ': ' + item.variation_value; }).join(', ');
    }

    function getRequiredVariationCount() {
        return document.querySelectorAll('.variant-pills').length;
    }

    function hasCompletedVariationSelection() {
        const requiredCount = getRequiredVariationCount();
        return requiredCount === 0 || Object.keys(selectedVariations).length >= requiredCount;
    }

    function getActiveVariantRow() {
        const variantKey = getSelectedVariantKey();
        return variantStockRows.find(function (row) { return row.combination_key === variantKey && Number(row.is_active); }) || null;
    }

    function getCurrentUnitPrice() {
        const activeVariant = getActiveVariantRow();
        if (activeVariant) {
            const variantRegular = Number(activeVariant.variant_price || 0);
            const variantSaleRaw = activeVariant.variant_sale_price;
            const variantSale = variantSaleRaw !== null && variantSaleRaw !== undefined && variantSaleRaw !== '' ? Number(variantSaleRaw || 0) : null;
            if (variantSale !== null && variantSale > 0 && variantSale < variantRegular) return variantSale;
            if (variantRegular > 0) return variantRegular;
        }
        return Number(baseProductPrice || 0);
    }

    function getCurrentRegularPrice() {
        const activeVariant = getActiveVariantRow();
        if (activeVariant && activeVariant.variant_price !== null && activeVariant.variant_price !== undefined && activeVariant.variant_price !== '') return Number(activeVariant.variant_price || 0);
        return Number(baseProductRegularPrice || 0);
    }

    function getCurrentSalePrice() {
        const activeVariant = getActiveVariantRow();
        if (activeVariant) {
            const variantRegular = Number(activeVariant.variant_price || 0);
            const variantSaleRaw = activeVariant.variant_sale_price;
            const variantSale = variantSaleRaw !== null && variantSaleRaw !== undefined && variantSaleRaw !== '' ? Number(variantSaleRaw || 0) : null;
            if (variantSale !== null && variantSale > 0 && variantSale < variantRegular) return variantSale;
            return null;
        }
        return baseProductSalePrice !== null ? Number(baseProductSalePrice || 0) : null;
    }

    function getCurrentWeight() {
        const activeVariant = getActiveVariantRow();
        if (activeVariant && activeVariant.variant_weight_grams !== undefined && activeVariant.variant_weight_grams !== null) return Number(activeVariant.variant_weight_grams || 0);
        return 0;
    }

    function getCurrentImage() {
        const activeVariant = getActiveVariantRow();
        if (activeVariant && activeVariant.image_url) return activeVariant.image_url;
        return defaultProductImageUrl;
    }

    function isVariantOutOfStock(variantRow) {
        if (!variantRow) return false;
        const stockMode = String(variantRow.stock_mode || '').trim().toLowerCase();
        if (stockMode === 'track_stock') {
            return Number(variantRow.stock_qty || 0) <= 0;
        }
        if (stockMode === 'manual_out_of_stock') {
            return String(variantRow.manual_stock_status || '').trim().toLowerCase() !== 'in_stock';
        }
        return false;
    }

    function isPurchaseBlocked() {
        const requiredCount = getRequiredVariationCount();
        const selectionComplete = hasCompletedVariationSelection();
        const activeVariant = selectionComplete ? getActiveVariantRow() : null;
        if (requiredCount > 0 && !selectionComplete) {
            return true;
        }
        if (requiredCount > 0 && selectionComplete && !activeVariant) {
            return true;
        }
        if (activeVariant) return isVariantOutOfStock(activeVariant);
        if (requiredCount === 0) {
            const stockStatus = String(initialStockStatus || '').trim().toLowerCase();
            return stockStatus === 'out_of_stock' || (stockStatus === 'track_stock' && Number(initialStockQty || 0) <= 0);
        }
        return String(initialStockStatus || '').trim().toLowerCase() === 'out_of_stock';
    }

    function updatePurchaseButtonsState() {
        const disable = isPurchaseBlocked();
        document.querySelectorAll('[data-purchase-action]').forEach(function (button) {
            button.disabled = disable;
            button.setAttribute('aria-disabled', disable ? 'true' : 'false');
        });
    }

    function updateDisplayedVariantImage() {
        const imageUrl = getCurrentImage();
        const mainImg = document.querySelector('.gallery-slider .gallery-img.current');
        if (!mainImg || !imageUrl) return;
        const picture = mainImg.closest('picture');
        if (picture) {
            picture.querySelectorAll('source').forEach(function (source) { source.setAttribute('srcset', imageUrl); });
        }
        mainImg.setAttribute('src', imageUrl);
    }

    function updateDisplayedKokoPlan(basePrice) {
        const kokoPlan = document.getElementById('productKokoPlan');
        const kokoPlanText = document.getElementById('productKokoPlanText');
        if (!kokoPlan || !kokoPlanText) return;
        const normalizedBasePrice = Number(basePrice || 0);
        if (normalizedBasePrice <= 0) { kokoPlan.style.display = 'none'; return; }
        const handlingFee = kokoHandlingFeePercentage > 0 ? normalizedBasePrice * (kokoHandlingFeePercentage / 100) : 0;
        kokoPlanText.textContent = 'or 3 x ' + formatKokoInstallment((normalizedBasePrice + handlingFee) / 3);
        kokoPlan.style.display = 'inline-flex';
    }

    function updateDisplayedPrice() {
        const currentPriceEl = document.getElementById('productCurrentPrice');
        const oldPriceEl = document.getElementById('productOldPrice');
        if (!currentPriceEl) return;
        const currentPrice = getCurrentUnitPrice();
        const regularPrice = getCurrentRegularPrice();
        const salePrice = getCurrentSalePrice();
        currentPriceEl.textContent = formatMoney(currentPrice);
        if (oldPriceEl) {
            if (salePrice !== null && salePrice < regularPrice) { oldPriceEl.style.display = ''; oldPriceEl.textContent = formatMoney(regularPrice); } else { oldPriceEl.style.display = 'none'; }
        }
        updateDisplayedKokoPlan(currentPrice);
    }

    function calculateShippingQuote(district) {
        const qtyInput = document.getElementById('qtyInput');
        const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : '1', 10) || 1);
        const subtotal = getCurrentUnitPrice() * qty;
        const chargeableWeight = (isFreeShipping || getCurrentWeight() <= 0) ? 0 : (getCurrentWeight() * qty);
        const districtName = String(district || '').trim();
        if (chargeableWeight <= 0) {
            return { subtotal: subtotal, shipping: 0, total: subtotal, chargeableWeight: 0, hasRate: true, district: districtName };
        }
        let firstKg = deliveryAllFirstKg;
        let additionalKg = deliveryAllAdditionalKg;
        let hasRate = true;
        if (!deliveryApplyAllDistricts) {
            if (!districtName || !deliveryRatesMap[districtName]) return { subtotal: subtotal, shipping: 0, total: subtotal, chargeableWeight: chargeableWeight, hasRate: false, district: districtName };
            firstKg = Number(deliveryRatesMap[districtName].first_kg_price || 0);
            additionalKg = Number(deliveryRatesMap[districtName].additional_kg_price || 0);
        }
        const shipping = chargeableWeight <= 1000 ? firstKg : firstKg + (Math.ceil((chargeableWeight - 1000) / 1000) * additionalKg);
        return { subtotal: subtotal, shipping: shipping, total: subtotal + shipping, chargeableWeight: chargeableWeight, hasRate: hasRate, district: districtName };
    }

    function calculateKokoHandlingFee(basePrice) {
        return orderMode === 'koko' && kokoHandlingFeePercentage > 0 ? Number(basePrice || 0) * (kokoHandlingFeePercentage / 100) : 0;
    }

    function updateOrderTotals() {
        const subtotalEl = document.getElementById('modalSubTotalDisplay');
        const shippingEl = document.getElementById('modalShippingDisplay');
        const totalEl = document.getElementById('modalGrandTotalDisplay');
        const handlingFeeRowEl = document.getElementById('modalHandlingFeeRow');
        const handlingFeeEl = document.getElementById('modalHandlingFeeDisplay');
        if (!subtotalEl || !shippingEl || !totalEl) return;
        const districtInput = document.getElementById('ordDistrict');
        const quote = calculateShippingQuote(districtInput ? districtInput.value : '');
        subtotalEl.textContent = formatMoney(quote.subtotal);
        shippingEl.textContent = quote.chargeableWeight === 0 ? 'Free' : (quote.hasRate ? formatMoney(quote.shipping) : 'Select district');
        const baseTotal = quote.hasRate || quote.chargeableWeight === 0 ? quote.total : quote.subtotal;
        const handlingFee = calculateKokoHandlingFee(baseTotal);
        if (handlingFeeRowEl && handlingFeeEl) { handlingFeeRowEl.style.display = handlingFee > 0 ? 'flex' : 'none'; handlingFeeEl.textContent = formatMoney(handlingFee); }
        totalEl.textContent = formatMoney(baseTotal + handlingFee);
        return quote;
    }

    function updateProductStockNotice(message, type) {
        const notice = document.getElementById('productStockNotice');
        if (!notice) return;
        if (!message) { notice.style.display = 'none'; notice.textContent = ''; return; }
        notice.style.display = 'inline-flex';
        notice.textContent = message;
        notice.style.background = type === 'error' ? '#fff0f0' : (type === 'success' ? '#eef8f0' : '#f4f4f4');
        notice.style.color = type === 'error' ? '#a43838' : (type === 'success' ? '#17663b' : '#555');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatWeightLabel(weightGrams) {
        const weight = Number(weightGrams || 0);
        if (!weight || weight <= 0) return 'Not specified';
        return weight >= 1000 ? (weight / 1000).toFixed(weight % 1000 === 0 ? 0 : 1) + ' kg' : weight + ' g';
    }

    function updateVariationAvailability() {
        const requiredCount = getRequiredVariationCount();
        const clearBtn = document.getElementById('clearStockFilterBtn');
        if (clearBtn) clearBtn.style.display = Object.keys(selectedVariations).length > 0 ? 'inline-flex' : 'none';
        if (requiredCount === 0) { updateProductStockNotice('', 'neutral'); updateDisplayedPrice(); updateDisplayedVariantImage(); updateOrderTotals(); updatePurchaseButtonsState(); return; }
        if (!hasCompletedVariationSelection()) { updateProductStockNotice('Select all options to see stock status.', 'neutral'); updateDisplayedPrice(); updateDisplayedVariantImage(); updateOrderTotals(); updatePurchaseButtonsState(); return; }
        const activeVariant = getActiveVariantRow();
        if (!activeVariant) { updateProductStockNotice('Out of stock', 'error'); updateDisplayedPrice(); updateDisplayedVariantImage(); updateOrderTotals(); updatePurchaseButtonsState(); return; }
        const blocked = isVariantOutOfStock(activeVariant);
        updateProductStockNotice(blocked ? 'Out of stock' : 'In Stock', blocked ? 'error' : 'success');
        updateDisplayedPrice(); updateDisplayedVariantImage(); updateOrderTotals(); updatePurchaseButtonsState();
    }

    function selectVariation(el) {
        const section = el.closest('.summary-section');
        if (!section) return;
        section.querySelectorAll('.variant-pill').forEach(function (item) { item.classList.remove('active'); item.setAttribute('aria-pressed', 'false'); });
        el.classList.add('active');
        el.setAttribute('aria-pressed', 'true');
        const variationName = el.dataset.variationName;
        selectedVariations[variationName] = { variation_id: Number(el.dataset.variationId || 0), variation_name: variationName, variation_value_id: Number(el.dataset.valueId || 0), variation_value: el.dataset.valueLabel || el.textContent.trim() };
        updateVariationAvailability();
    }

    function clearVariationSelection() {
        selectedVariations = {};
        document.querySelectorAll('.variant-pill').forEach(function (pill) { pill.classList.remove('active'); pill.setAttribute('aria-pressed', 'false'); });
        updateProductStockNotice('', 'neutral'); updateDisplayedPrice(); updateDisplayedVariantImage(); updateOrderTotals(); updatePurchaseButtonsState();
    }

    function addToCartFromProductPage() {
        const qty = parseInt(document.getElementById('qtyInput').value, 10) || 1;
        const variantKey = getSelectedVariantKey();
        const variantStr = getVariantText();
        if (!hasCompletedVariationSelection() && getRequiredVariationCount() > 0) { showProductToast('Please choose all product options before adding to basket.', 'error'); return; }
        if (isPurchaseBlocked()) { showProductToast('This item is not available right now.', 'error'); return; }
        fetch('<?= htmlspecialchars($baseUrl) ?>cart/add', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, title: productTitle, price: getCurrentUnitPrice(), img: getCurrentImage(), variants: variantStr, variant_key: variantKey, quantity: qty })
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (!data || !data.success) throw new Error((data && data.message) ? data.message : 'Failed to add to cart');
            if (typeof window.updateCartUi === 'function') {
                window.updateCartUi(data.count || 0);
            }
            window.dispatchEvent(new CustomEvent('cart:changed', { detail: { count: data.count || 0 } }));
            showCartConfirm('Added to basket.');
        }).catch(function (err) { showProductToast(err.message || 'Failed to add to cart', 'error'); });
    }

    function openPaymentMethodSheet() { if (isPurchaseBlocked()) { showProductToast('This item is not available right now.', 'error'); return; } document.getElementById('paymentMethodSheet').style.display = 'flex'; }
    function closePaymentMethodSheet() { document.getElementById('paymentMethodSheet').style.display = 'none'; }
    function openOrderModal() { hydrateCustomerProfile(); document.getElementById('orderModal').style.display = 'flex'; updateOrderTotals(); }
    function closeOrderModal() { document.getElementById('orderModal').style.display = 'none'; }
    function updateOrderButtonLabel() {
        const submitButton = document.getElementById('orderSubmitButton');
        const bankDetailsBox = document.getElementById('bankTransferDetailsBox');
        if (!submitButton) return;
        submitButton.textContent = orderMode === 'whatsapp' ? 'Send via WhatsApp' : (orderMode === 'payhere' ? 'Proceed to Card Payments' : (orderMode === 'koko' ? 'Proceed to KOKO Payments' : (orderMode === 'bank_transfer' ? 'Submit Bank Transfer Order' : 'Place Order')));
        if (bankDetailsBox) bankDetailsBox.style.display = orderMode === 'bank_transfer' ? 'block' : 'none';
        updateOrderTotals();
    }
    function choosePaymentMethod(mode) { orderMode = mode; closePaymentMethodSheet(); openOrderModal(); updateOrderButtonLabel(); }
    function buildOrderPayload() { return { product_id: productId, quantity: parseInt(document.getElementById('qtyInput').value, 10) || 1, variants: getVariantText(), variant_key: getSelectedVariantKey(), customer_name: document.getElementById('ordName').value.trim(), email: document.getElementById('ordEmail').value.trim(), phone: document.getElementById('ordPhone1').value.trim(), phone_alt: document.getElementById('ordPhone2').value.trim(), address: document.getElementById('ordAddress').value.trim(), city: document.getElementById('ordCity').value.trim(), district: document.getElementById('ordDistrict').value.trim(), note: document.getElementById('ordNote').value.trim() }; }
    function validateOrderPayload(payload) { if (!payload.customer_name || !payload.email || !payload.phone || !payload.address || !payload.city || !payload.district) { alert('Please fill in all required fields.'); return false; } if (!hasCompletedVariationSelection() && getRequiredVariationCount() > 0) { alert('Please choose all product options before checkout.'); return false; } return true; }
    function openWhatsAppOrder(payload) { if (!shopWhatsappTarget) { alert('WhatsApp ordering is not configured yet.'); return; } const lines = ['*New Product Order*', 'Product: ' + productTitle, 'Quantity: ' + payload.quantity, payload.variants ? 'Variant: ' + payload.variants : '', 'Name: ' + payload.customer_name, 'Email: ' + payload.email, 'Phone: ' + payload.phone, payload.phone_alt ? 'Phone 2: ' + payload.phone_alt : '', 'Address: ' + payload.address, 'City: ' + payload.city, 'District: ' + payload.district, payload.note ? 'Note: ' + payload.note : ''].filter(Boolean); window.open('https://wa.me/' + shopWhatsappTarget + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener'); }
    async function submitOrder() {
        const payload = buildOrderPayload(); if (!validateOrderPayload(payload)) return;
        saveCustomerProfile(payload);
        if (orderMode === 'whatsapp') { openWhatsAppOrder(payload); closeOrderModal(); return; }
        try {
            if (recaptchaCheckoutEnabled) {
                payload.g_recaptcha_response = await getRecaptchaToken();
                payload.g_recaptcha_action = 'checkout_order';
            }
        } catch (error) {
            alert(error && error.message ? error.message : 'Unable to verify checkout right now.');
            return;
        }
        const routeMap = { cod: 'order/startCodSingle', payhere: 'order/startPayhereSingle', koko: 'order/startKokoSingle', bank_transfer: 'order/startBankTransferSingle' };
        const endpoint = <?= json_encode($baseUrl) ?> + (routeMap[orderMode] || routeMap.cod);
        const form = document.createElement('form'); form.method = 'POST'; form.action = endpoint;
        Object.keys(payload).forEach(function (key) { const input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = String(payload[key] ?? ''); form.appendChild(input); });
        document.body.appendChild(form); form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const slider = document.querySelector('[data-gallery-slider]');
        const modalSlider = document.getElementById('imgModalSlider');
        const districtInput = document.getElementById('ordDistrict');
        const prevButton = document.querySelector('[data-gallery-prev]');
        const nextButton = document.querySelector('[data-gallery-next]');
        if (slider) {
            slider.addEventListener('scroll', function () {
                const width = slider.clientWidth || 1; const index = Math.round(slider.scrollLeft / width);
                syncGalleryUI(index);
            });
            slider.style.webkitOverflowScrolling = 'touch'; slider.style.touchAction = 'auto';
            // Reset any browser-restored horizontal scroll so single-image products don't appear blank.
            scrollToGallery(0, 'auto');
        }
        if (prevButton) prevButton.addEventListener('click', function () { scrollToGallery(getCurrentGalleryIndex() - 1); });
        if (nextButton) nextButton.addEventListener('click', function () { scrollToGallery(getCurrentGalleryIndex() + 1); });
        if (modalSlider) modalSlider.addEventListener('scroll', function () { const width = modalSlider.offsetWidth || 1; modalImageIndex = Math.round(modalSlider.scrollLeft / width); });
        if (districtInput) districtInput.addEventListener('change', updateOrderTotals);
        const qtyInput = document.getElementById('qtyInput'); if (qtyInput) qtyInput.addEventListener('change', updateOrderTotals);
        ['ordName','ordEmail','ordAddress','ordCity','ordDistrict','ordPhone1','ordPhone2','ordNote'].forEach(function (fieldId) {
            const field = document.getElementById(fieldId);
            if (!field) return;
            field.addEventListener('input', captureCustomerProfile);
            field.addEventListener('change', captureCustomerProfile);
        });
        document.querySelectorAll('.variant-pill').forEach(function (pill) {
            pill.addEventListener('click', function () { selectVariation(this); });
            pill.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectVariation(this);
                }
            });
        });
        syncGalleryUI(0);
        hydrateCustomerProfile();
        updateDisplayedPrice(); updateOrderTotals(); updateDisplayedVariantImage();
        updatePurchaseButtonsState();
    });
    document.addEventListener('keydown', handleImageModalKeydown);
</script>
</body>
</html>
