<?php
$hide_mobile_welcome = true;
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/DeliveryHelper.php';
require_once ROOT_PATH . 'helpers/FooterHelper.php';
require_once ROOT_PATH . 'helpers/KokoGateway.php';
require_once ROOT_PATH . 'helpers/RecaptchaHelper.php';
require_once 'views/layouts/customer_layout.php';
customer_layout_start([
    'seo_title' => $seo_title ?? ($title ?? ''),
    'seo_description' => $seo_description ?? '',
    'seo_image' => $seo_image ?? '',
    'seo_canonical' => $seo_canonical ?? '',
    'seo_type' => $seo_type ?? 'website',
    'seo_robots' => $seo_robots ?? '',
    'seo_json_ld' => $seo_json_ld ?? []
]);

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$currency = $settings['currency_symbol'] ?? 'LKR';
$cartItems = is_array($cart ?? null) ? array_values($cart) : [];
$cartCount = 0;
$subtotal = 0.0;
$jsItems = [];
$jsCartWhatsappItems = [];
foreach ($cartItems as $idx => $item) {
    $qty = max(1, (int)($item['qty'] ?? 1));
    $price = (float)($item['price'] ?? 0);
    $weight = max(0, (int)($item['weight_grams'] ?? 0));
    $subtotal += $price * $qty;
    $cartCount += $qty;
    $jsItems[] = ['index' => $idx, 'qty' => $qty, 'price' => $price, 'weight' => $weight, 'free' => !empty($item['is_free_shipping'])];
    $jsCartWhatsappItems[] = [
        'title' => trim((string)($item['title'] ?? 'Product')),
        'variants' => trim((string)($item['variants'] ?? '')),
        'qty' => $qty,
        'price' => $price
    ];
}

$districts = isset($deliveryDistricts) && is_array($deliveryDistricts) ? $deliveryDistricts : DeliveryHelper::districtList();
$selectedDistrict = trim((string)($_GET['district'] ?? ''));
if ($selectedDistrict === '') $selectedDistrict = !empty($settings['delivery_apply_all_districts']) ? 'All Districts' : (string)($districts[0] ?? '');
$ratesMap = is_array($deliveryRatesMap ?? null) ? $deliveryRatesMap : [];
$quote = DeliveryHelper::calculateShipping($cartItems, $selectedDistrict, $settings, $ratesMap);
if (empty($quote['has_rate']) && !empty($districts)) {
    $selectedDistrict = (string)$districts[0];
    $quote = DeliveryHelper::calculateShipping($cartItems, $selectedDistrict, $settings, $ratesMap);
}
$shippingFee = (float)($quote['shipping_fee'] ?? 0);
$total = (float)($quote['total'] ?? $subtotal);
$shippingLabel = ($quote['chargeable_weight_grams'] ?? 0) <= 0 ? 'Free' : (!empty($quote['has_rate']) ? $currency . ' ' . number_format($shippingFee, 2) : 'Select district');
$summaryNote = !empty($settings['delivery_apply_all_districts']) ? 'Shipping is estimated from weight and district.' : 'Choose a district to calculate shipping.';
$cartHasBlockedItems = false;
foreach ($cartItems as $cartItem) {
    if (!empty($cartItem['purchase_blocked'])) {
        $cartHasBlockedItems = true;
        break;
    }
}

$shopWhatsapp = preg_replace('/[^0-9]/', '', (string)($settings['shop_whatsapp'] ?? '')) ?: preg_replace('/[^0-9]/', '', (string)($settings['social_whatsapp'] ?? ''));
$whatsappLink = $shopWhatsapp !== '' ? 'https://wa.me/' . $shopWhatsapp : '';
$payhereReady = !empty($settings['payhere_enabled']) && trim((string) ($settings['payhere_merchant_id'] ?? '')) !== '' && trim((string) ($settings['payhere_merchant_secret'] ?? '')) !== '';
$kokoReady = class_exists('KokoGateway') && KokoGateway::isConfigured($settings);
$recaptchaCheckoutEnabled = RecaptchaHelper::shouldProtectCheckout($settings);
$recaptchaSiteKey = $recaptchaCheckoutEnabled ? RecaptchaHelper::siteKey($settings) : '';
$modes = [];
if ($payhereReady) $modes[] = ['key' => 'payhere', 'label' => 'Card Payments', 'icon' => 'fa-solid fa-credit-card'];
if ($kokoReady) $modes[] = ['key' => 'koko', 'label' => 'KOKO Payments', 'icon' => 'fa-solid fa-wallet'];
if (!empty($settings['whatsapp_ordering_enabled']) && $whatsappLink !== '') $modes[] = ['key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp'];
if (!empty($settings['cod_enabled'])) $modes[] = ['key' => 'cod', 'label' => 'Cash on Delivery', 'icon' => 'fa-solid fa-truck-fast'];
if (!empty($settings['bank_transfer_enabled']) && trim((string)($settings['bank_transfer_details'] ?? '')) !== '') $modes[] = ['key' => 'bank_transfer', 'label' => 'Bank Transfer', 'icon' => 'fa-solid fa-building-columns'];
if (!$modes) $modes[] = ['key' => 'cod', 'label' => 'Checkout', 'icon' => 'fa-solid fa-check'];
?>

<style>
    .cart-page{padding:24px 0 72px;font-family:"Manrope",sans-serif}
    .cart-page h1,.cart-page h2,.cart-page h3,.cart-page h4,.cart-page h5{font-family:"Noto Serif",serif;font-weight:400}
    .cart-page .container{width:min(1600px,calc(100% - 96px));padding-left:24px;margin:0 auto;box-sizing:border-box}
    .cart-page .cart-shell{display:grid;gap:24px}
    .cart-page .cart-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:10px}
    .cart-page .cart-hero p{font-size:14px;line-height:1.8;max-width:760px;color:#6d6665}
    .cart-page .cart-kicker{display:inline-flex;align-items:center;gap:10px;font-size:10px;font-weight:800;letter-spacing:.24em;text-transform:uppercase;color:#7c7777;margin-bottom:10px}
    .cart-page .cart-kicker:before{content:"";width:34px;height:1px;background:rgba(31,31,31,.18)}
    .cart-page .cart-hero h1{margin:0;font-family:sans-serif;font-size:clamp(32px,3.6vw,48px);line-height:1;font-weight:400;color:#111;letter-spacing:-.04em}
    .cart-page .cart-clear{font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--accent-red, var(--primary));text-decoration:none;white-space:nowrap}
    .cart-page .cart-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(330px,.65fr);gap:24px;align-items:start}
    .cart-page .cart-panel{background:var(--surface);border:1px solid rgba(31,31,31,.08);box-shadow:0 18px 42px rgba(31,31,31,.06)}
    .cart-page .cart-panel-head{display:flex;justify-content:space-between;gap:12px;padding:20px 22px;border-bottom:1px solid rgba(31,31,31,.08)}
    .cart-page .cart-panel-head strong{display:block;font-size:15px;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:#111}
    .cart-page .cart-panel-head small{display:block;margin-top:4px;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#7c7777}
    .cart-page .cart-line{display:grid;grid-template-columns:112px minmax(0,1fr);gap:18px;padding:20px 22px;border-bottom:1px solid rgba(31,31,31,.08)}
    .cart-page .cart-line:last-child{border-bottom:none}
    .cart-page .cart-media{width:112px;aspect-ratio:1/1;border:1px solid rgba(31,31,31,.12);background:#fafafa;overflow:hidden;display:block}
    .cart-page .cart-media img{width:100%;height:100%;object-fit:contain;display:block;border-radius:0 !important}
    .cart-page .cart-line-main{display:grid;gap:12px;min-width:0}
    .cart-page .cart-line-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
    .cart-page .cart-line-title{margin:0;font-family:sans-serif;font-size:18px;line-height:1.3;font-weight:400;color:#111}
    .cart-page .cart-line-total{font-size:18px;font-weight:900;color:#111;white-space:nowrap}
    .cart-page .cart-chip-row{display:flex;flex-wrap:wrap;gap:8px}
    .cart-page .cart-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border:1px solid rgba(31,31,31,.10);font-size:10px;font-weight:800;letter-spacing:.11em;text-transform:uppercase;color:#666;background:var(--surface)}
    .cart-page .cart-chip.shipping{background:#f4fbf4;color:#17663b;border-color:rgba(23,102,59,.18)}
    .cart-page .cart-chip.blocked{background:color-mix(in srgb, #a43838 10%, var(--surface));color:#a43838;border-color:rgba(164,56,56,.18)}
    .cart-page .cart-line-price{display:flex;gap:10px;align-items:baseline;flex-wrap:wrap}
    .cart-page .cart-line-price .current{font-size:22px;font-weight:300;color:var(--accent-red, var(--primary));letter-spacing:.01em}
    .cart-page .cart-line-price .meta{font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#777}
    .cart-page .cart-line-variant{font-size:12px;line-height:1.7;color:#666}
    .cart-page .cart-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .cart-page .qty-box{display:inline-flex;align-items:center;height:46px;border:1px solid rgba(31,31,31,.14);background:var(--surface)}
    .cart-page .qty-box button{width:46px;height:46px;border:none;background:var(--surface);color:#111;font-size:18px;font-weight:900;cursor:pointer}
    .cart-page .qty-box button:first-child{border-right:1px solid rgba(31,31,31,.14)}
    .cart-page .qty-box button:last-child{border-left:1px solid rgba(31,31,31,.14)}
    .cart-page .qty-box span{width:52px;text-align:center;font-size:14px;font-weight:900;color:#111}
    .cart-page .remove-btn{width:46px;height:46px;border:1px solid rgba(31,31,31,.14);background:var(--surface);color:var(--accent-red, var(--primary));display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
    .cart-page .remove-btn:hover{background:color-mix(in srgb, var(--accent-red, var(--primary)) 7%, #ffffff)}
    .cart-page .cart-side{position:sticky;top:104px;padding:28px 26px 26px;display:grid;gap:22px;background:var(--surface)}
    .cart-page .summary-section{display:grid;gap:12px;padding-top:2px}
    .cart-page .summary-title{margin:0;font-family:sans-serif;font-size:24px;line-height:1.08;font-weight:400;color:#111;letter-spacing:-.02em}
    .cart-page .cart-side h2{margin:0;font-family:sans-serif;font-size:24px;line-height:1.08;font-weight:400;color:#111;letter-spacing:-.02em}
    .cart-page .summary-box{display:grid;gap:14px;padding-top:4px}
    .cart-page .summary-row,.cart-page .summary-total{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
    .cart-page .summary-row .label,.cart-page .summary-total .label{font-size:10px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:#8a8383}
    .cart-page .summary-row .value{font-size:14px;font-weight:800;color:#111;text-align:right;line-height:1.4}
    .cart-page .summary-row .value.district-value{width:240px;max-width:100%}
    .cart-page .summary-total{padding-top:16px;border-top:1px dashed rgba(31,31,31,.12)}
    .cart-page .summary-total .value{font-size:28px;line-height:1.05;font-weight:900;color:#111;text-align:right;letter-spacing:-.02em}
    .cart-page .district-select{width:100%;min-height:46px;border:1px solid rgba(31,31,31,.14);background:var(--surface);color:#111;padding:0 14px;font:inherit;font-size:14px;font-weight:700}
    .cart-page .summary-note{margin:0;font-size:11px;line-height:1.75;color:#6d6665}
    .cart-page .payment-pick{display:grid;gap:12px;padding-top:2px}
    .cart-page .payment-pick-label{display:inline-flex;align-items:center;gap:10px;font-size:10px;font-weight:800;letter-spacing:.24em;text-transform:uppercase;color:#8a8383}
    .cart-page .payment-pick-label:before{content:"";width:34px;height:1px;background:rgba(31,31,31,.18)}
    .cart-page .payment-method-toggle{display:grid;grid-template-columns:1fr 1fr;gap:10px;width:100%}
    .cart-page .payment-method-toggle-btn{width:100%;min-height:40px;padding:0 16px;border-radius:0;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,filter .2s ease,background .2s ease,color .2s ease,border-color .2s ease}
    .cart-page .payment-method-toggle-btn.pay-now{background:#fff;border:1px solid #b68a2d;color:#b68a2d}
    .cart-page .payment-method-toggle-btn.pay-later{background:#fff;border:1px solid #111;color:#111}
    .cart-page .payment-method-toggle-btn:hover{transform:translateY(-1px)}
    .cart-page .payment-method-toggle-btn:active{transform:translateY(0) scale(.98)}
    .cart-page .payment-method-toggle-btn.is-active{box-shadow:0 10px 22px rgba(31,31,31,.18);transform:translateY(-1px)}
    .cart-page .payment-method-toggle-btn.pay-now.is-active{background:linear-gradient(135deg,#b68a2d 0%,#d4af37 52%,#a8791d 100%);border-color:#b68a2d;color:#fff;filter:saturate(1.08) brightness(1.02)}
    .cart-page .payment-method-toggle-btn.pay-later.is-active{background:#111;border-color:#111;color:#fff}
    .cart-page .payment-method-toggle-btn:disabled{opacity:.45;cursor:not-allowed}
    .cart-page .payment-choice-grid{display:grid;gap:10px}
    .cart-page .payment-method-card{
        position:relative;
        overflow:hidden;
        display:flex;
        gap:14px;
        align-items:center;
        width:100%;
        padding:14px 16px 14px 18px;
        border:1px solid rgba(31,31,31,.10);
        background:linear-gradient(180deg,var(--surface) 0%, color-mix(in srgb, var(--surface) 96%, #fcfcfc) 100%);
        cursor:pointer;
        text-align:left;
        box-shadow:0 10px 22px rgba(31,31,31,.05);
        transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease
    }
    .cart-page .payment-method-card::before{
        content:"";
        position:absolute;
        left:0;
        top:0;
        width:4px;
        height:100%;
        background:rgba(31,31,31,.14)
    }
    .cart-page .payment-method-card:hover{
        transform:translateY(-1px);
        border-color:rgba(182,138,45,.28);
        box-shadow:0 14px 28px rgba(31,31,31,.10)
    }
    .cart-page .payment-method-card:disabled{
        opacity:.45;
        cursor:not-allowed;
        transform:none;
        box-shadow:0 10px 22px rgba(31,31,31,.05);
        pointer-events:none;
    }
    .cart-page .payment-method-icon{
        width:46px;
        height:46px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:0;
        background:#f4f4f4;
        color:#111;
        font-size:18px;
        flex-shrink:0;
        box-shadow:inset 0 0 0 1px rgba(31,31,31,.04)
    }
    .cart-page .payment-method-card.payhere{border-color:#d4af37;background:linear-gradient(135deg,#b68a2d 0%,#d4af37 52%,#a8791d 100%);color:#111111}
    .cart-page .payment-method-card.payhere::before{background:#d4af37}
    .cart-page .payment-method-card.payhere .payment-method-icon{background:rgba(255,255,255,.34);color:#111111}
    .cart-page .payment-method-card.koko{border-color:#e8b9d5;background:linear-gradient(135deg,#f4d0e5 0%,#f8e0ee 52%,#e9b7d4 100%);color:#111111}
    .cart-page .payment-method-card.koko::before{background:#e8b9d5}
    .cart-page .payment-method-card.koko .payment-method-icon{background:rgba(255,255,255,.45);color:#111111}
    .cart-page .payment-method-card.whatsapp{border-color:#289b26;background:#289b26;color:#ffffff}
    .cart-page .payment-method-card.whatsapp::before{background:#289b26}
    .cart-page .payment-method-card.whatsapp .payment-method-icon{background:rgba(255,255,255,.24);color:#ffffff}
    .cart-page .payment-method-card.cod{border-color:#d5d7dc;background:linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 55%,#d1d5db 100%);color:#1f2937}
    .cart-page .payment-method-card.cod::before{background:#cbd5e1}
    .cart-page .payment-method-card.cod .payment-method-icon{background:rgba(255,255,255,.78);color:#1f2937}
    .cart-page .payment-method-card.bank_transfer{border-color:#4b5563;background:linear-gradient(135deg,#4b5563 0%,#374151 55%,#1f2937 100%);color:#ffffff}
    .cart-page .payment-method-card.bank_transfer::before{background:#4b5563}
    .cart-page .payment-method-card.bank_transfer .payment-method-icon{background:rgba(255,255,255,.18);color:#ffffff}
    .cart-page .payment-method-copy{min-width:0;flex:1}
    .cart-page .payment-method-copy strong{display:block;font-size:14px;font-weight:900;letter-spacing:.01em;color:inherit;margin-bottom:4px;line-height:1.3}
    .cart-page .payment-method-copy small{display:block;color:inherit;opacity:.82;font-size:11px;line-height:1.55}
    .cart-page .payment-method-arrow{color:inherit;opacity:.72;font-size:14px;flex-shrink:0}
    .cart-page .summary-actions{display:flex;gap:12px;align-items:stretch}
    .cart-page .btn-action,.cart-page .btn-secondary{min-height:54px;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 18px;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;text-decoration:none;border-radius:0}
    .cart-page .btn-action{border:1px solid #111;background:var(--btn-addcart-bg, #111);color:var(--btn-addcart-text, #fff);flex:1 1 0}
    .cart-page .btn-buy{background:var(--btn-ordernow-bg, var(--primary));border-color:var(--btn-ordernow-bg, var(--primary));color:var(--btn-ordernow-text, #fff)}
    .cart-page .btn-secondary{border:1px solid rgba(31,31,31,.14);background:var(--surface);color:#111}
    .cart-page .empty-card{max-width:760px;margin:0 auto;padding:42px 24px;background:var(--surface);border:1px solid rgba(31,31,31,.08);box-shadow:0 18px 42px rgba(31,31,31,.06);text-align:center}
    .cart-page .empty-card h2{margin:0 0 10px;font-size:30px;line-height:1.06;font-weight:900;color:#111}
    .cart-page .empty-card p{margin:0 auto 22px;max-width:540px;color:#6d6665;font-size:14px;line-height:1.8}
    .cart-page .empty-actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}
    .cart-page .modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.58);z-index:9999}
    .cart-page .modal{width:min(92vw,610px);max-height:90vh;overflow-y:auto;background:var(--surface);border:1px solid rgba(31,31,31,.10);box-shadow:none;padding:24px 24px 22px;border-radius:0}
    .cart-page .modal-head{position:relative;display:grid;gap:6px;justify-items:center;text-align:center;margin-bottom:18px;padding-right:42px}
    .cart-page .modal-head h3{margin:0;font-size:28px;line-height:1.05;font-weight:900;color:#111;letter-spacing:-.03em;font-family:sans-serif}
    .cart-page .modal-head p{margin:0;max-width:430px;color:#777;font-size:13px;line-height:1.6}
    .cart-page .modal-close{position:absolute;right:0;top:0;width:38px;height:38px;border:1px solid rgba(31,31,31,.14);background:var(--surface);color:#111;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
    .cart-page .modal-form{display:grid;gap:12px}
    .cart-page .input-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .cart-page .field label{display:block;margin-bottom:5px;font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#7c7777}
    .cart-page .field input,.cart-page .field textarea,.cart-page .field select{width:100%;min-height:46px;padding:10px 12px;border:1px solid rgba(31,31,31,.18);background:var(--surface);color:#111;font:inherit;box-shadow:inset 0 1px 0 rgba(255,255,255,.7);border-radius:0}
    .cart-page .field textarea{min-height:90px;resize:vertical}
    .cart-page .field input:focus,.cart-page .field textarea:focus,.cart-page .field select:focus{outline:none;border-color:rgba(182,138,45,.42);box-shadow:0 0 0 3px rgba(182,138,45,.10)}
    .cart-page .totals-box{background:#fafafa;border:1px solid rgba(31,31,31,.08);padding:16px 18px;display:grid;gap:10px;margin-top:4px}
    .cart-page .totals-row{display:flex;justify-content:space-between;gap:12px;font-size:13px}
    .cart-page .totals-row span{color:#7c7777}
    .cart-page .totals-row strong{font-size:14px;color:#111}
    .cart-page .modal-actions{display:flex;gap:10px;margin-top:2px}
    .cart-page .modal-actions button{flex:1;min-height:48px;border:1px solid rgba(31,31,31,.14);background:#f7f7f7;color:#111;font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;cursor:pointer}
    .cart-page .modal-actions button:disabled{opacity:.45;cursor:not-allowed}
    .cart-page .modal-actions .primary,.cart-page .modal-actions #checkoutSubmit{background:var(--btn-ordernow-bg, var(--primary)) !important;border-color:var(--btn-ordernow-bg, var(--primary)) !important;color:var(--btn-ordernow-text, #fff) !important;box-shadow:none}
    .cart-page .modal-actions #checkoutSubmit.checkout-submit-whatsapp{background:#289b26 !important;border-color:#289b26 !important;color:#fff !important}
    .cart-page .bank-details-box{display:none;background:#fee2e2;border:1px solid #fecaca;padding:14px}
    .cart-page .bank-details-box strong{display:block;font-size:13px;font-weight:900;color:#000;margin-bottom:6px}
    .cart-page .bank-details-box .text{font-size:12px;color:#000;line-height:1.7;white-space:pre-wrap}
    .cart-page .toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%) translateY(18px);opacity:0;pointer-events:none;z-index:10010;display:flex;align-items:center;gap:12px;min-width:240px;max-width:min(92vw,420px);padding:14px 18px;background:#111;color:#fff;box-shadow:0 16px 30px rgba(0,0,0,.18);transition:opacity .2s ease, transform .2s ease}
    .cart-page .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
    .cart-page .toast.success{background:#17663b}
    .cart-page .toast.error{background:#a43838}
    .cart-page .toast .text{font-size:13px;line-height:1.5;font-weight:700}
    .cart-page .order-flash{margin:0 0 16px;padding:14px 16px;border:1px solid rgba(182,138,45,.22);background:color-mix(in srgb, var(--accent-red, var(--primary)) 8%, #ffffff);color:#7a5a14;font-size:13px;line-height:1.7}
    .cart-page .order-flash strong{display:block;font-size:11px;letter-spacing:.18em;text-transform:uppercase;margin-bottom:4px}
    @media (max-width:1023px){
        .cart-page{padding:16px 0 48px}
        .cart-page .container{padding-left:14px !important;padding-right:14px !important}
        .cart-page .cart-hero{flex-direction:column;align-items:flex-start}
        .cart-page .cart-grid{grid-template-columns:1fr;gap:16px}
        .cart-page .cart-panel-head,.cart-page .cart-line,.cart-page .cart-side{padding-left:16px;padding-right:16px}
        .cart-page .cart-line{grid-template-columns:78px minmax(0,1fr);gap:12px;padding-top:16px;padding-bottom:16px}
        .cart-page .cart-media{width:78px}
        .cart-page .cart-line-main{gap:10px}
        .cart-page .cart-line-title{font-size:15px;line-height:1.25}
        .cart-page .cart-line-variant{font-size:11px;line-height:1.5}
        .cart-page .cart-chip{padding:3px 8px;font-size:9px;letter-spacing:.08em}
        .cart-page .cart-chip-row{gap:6px}
        .cart-page .cart-line-price{gap:8px}
        .cart-page .cart-line-price .current{font-size:16px;font-weight:600;letter-spacing:.01em;opacity:.86}
        .cart-page .cart-line-price .meta{font-size:10px;letter-spacing:.12em}
        .cart-page .cart-line-total{align-self:flex-end;font-size:16px}
        .cart-page .cart-line-top{flex-direction:column;gap:8px}
        .cart-page .cart-actions{gap:8px}
        .cart-page .qty-box{height:42px}
        .cart-page .qty-box button{width:42px;height:42px;font-size:16px}
        .cart-page .qty-box span{width:44px;font-size:13px}
        .cart-page .remove-btn{width:42px;height:42px}
        .cart-page .cart-side{position:static;top:auto}
        .cart-page .summary-total .value{font-size:24px}
        .cart-page .summary-actions{flex-direction:column}
        .cart-page .input-row{grid-template-columns:1fr}
        .cart-page .modal{padding:18px}
        .cart-page .summary-row .value.district-value{width:100%}
        .cart-page .modal-head{padding-right:0}
        .cart-page .modal-close{position:static;justify-self:center;margin-top:6px}
        .cart-page .modal-actions{flex-direction:column}
    }
</style>

<div class="cart-page">
    <div class="container">
        <div class="cart-hero">
            <div>
                <div class="cart-kicker">Shopping Cart</div>
                <h1>My Cart</h1>
                <p>Review your selected items and continue with the checkout method that matches your store setup.</p>
            </div>
            <?php if ($cartCount > 0): ?><a class="cart-clear" href="#" data-cart-clear>Clear Cart</a><?php endif; ?>
        </div>
        <?php if (!empty($_SESSION['order_error'])): ?>
            <div class="order-flash">
                <strong>Checkout notice</strong>
                <?= htmlspecialchars((string) $_SESSION['order_error']) ?>
            </div>
            <?php unset($_SESSION['order_error']); ?>
        <?php endif; ?>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-grid">
                <section class="cart-panel">
                    <div class="cart-panel-head">
                        <div>
                            <strong>Cart Items</strong>
                            <small><?= (int)$cartCount ?> item(s)</small>
                        </div>
                    </div>
                    <div data-cart-lines>
                        <?php foreach ($cartItems as $idx => $item): ?>
                            <?php
                            $qty = max(1, (int)($item['qty'] ?? 1));
                            $price = (float)($item['price'] ?? 0);
                            $line = $qty * $price;
                            $img = ImageHelper::uploadUrl((string)($item['img'] ?? ''), $baseUrl . 'assets/images/placeholder.png');
                            $title = (string)($item['title'] ?? 'Product');
                            $variant = trim((string)($item['variants'] ?? ''));
                            $weight = max(0, (int)($item['weight_grams'] ?? 0));
                            ?>
                            <article class="cart-line" data-cart-line data-index="<?= (int)$idx ?>" data-qty="<?= (int)$qty ?>" data-price="<?= htmlspecialchars(number_format($price, 2, '.', '')) ?>" data-weight="<?= (int)$weight ?>" data-free="<?= !empty($item['is_free_shipping']) ? '1' : '0' ?>" data-stock-blocked="<?= !empty($item['purchase_blocked']) ? '1' : '0' ?>">
                                <a class="cart-media" href="<?= htmlspecialchars($baseUrl . 'shop') ?>"><img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($title) ?>" loading="lazy" decoding="async"></a>
                                <div class="cart-line-main">
                                    <div class="cart-line-top">
                                        <div>
                                            <h2 class="cart-line-title"><?= htmlspecialchars($title) ?></h2>
                                            <?php if ($variant !== ''): ?><div class="cart-line-variant"><?= htmlspecialchars($variant) ?></div><?php endif; ?>
                                            <div class="cart-chip-row">
                                                <?php if (!empty($item['purchase_blocked'])): ?><span class="cart-chip blocked"><i class="fa-solid fa-circle-exclamation"></i> Out of Stock</span><?php endif; ?>
                                                <?php if (!empty($item['is_free_shipping'])): ?><span class="cart-chip shipping"><i class="fa-solid fa-truck-fast"></i> Free Shipping</span><?php endif; ?>
                                                <?php if ($weight > 0): ?><span class="cart-chip"><i class="fa-solid fa-weight-hanging"></i> <?= $weight >= 1000 ? htmlspecialchars(number_format($weight / 1000, 1)) . ' kg' : (int)$weight . ' g' ?></span><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="cart-line-total" data-line-total><?= htmlspecialchars($currency . ' ' . number_format($line, 2)) ?></div>
                                    </div>
                                    <div class="cart-line-price"><span class="current" data-line-unit><?= htmlspecialchars($currency . ' ' . number_format($price, 2)) ?></span><span class="meta">Each</span></div>
                                    <div class="cart-actions">
                                        <div class="qty-box"><button type="button" data-qty-minus>-</button><span data-line-qty><?= (int)$qty ?></span><button type="button" data-qty-plus>+</button></div>
                                        <button type="button" class="remove-btn" data-cart-remove aria-label="Remove item"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <aside class="cart-panel cart-side" data-cart-has-blocked="<?= $cartHasBlockedItems ? '1' : '0' ?>">
                    <div class="summary-section">
                        <div class="cart-kicker" style="margin-bottom:8px">Checkout Summary</div>
                        <h2 class="summary-title">Order Summary</h2>
                    </div>
                    <div class="summary-box">
                        <div class="summary-row"><span class="label">Subtotal</span><span class="value" data-summary-subtotal><?= htmlspecialchars($currency . ' ' . number_format($subtotal, 2)) ?></span></div>
                        <div class="summary-row"><span class="label">District</span><span class="value district-value"><select class="district-select" data-district-select><?php if (!empty($settings['delivery_apply_all_districts'])): ?><option value="All Districts" <?= $selectedDistrict === 'All Districts' ? 'selected' : '' ?>>All Districts</option><?php endif; ?><?php foreach ($districts as $district): ?><option value="<?= htmlspecialchars($district) ?>" <?= $selectedDistrict === $district ? 'selected' : '' ?>><?= htmlspecialchars($district) ?></option><?php endforeach; ?></select></span></div>
                        <div class="summary-row"><span class="label">Shipping</span><span class="value" data-summary-shipping><?= htmlspecialchars($shippingLabel) ?></span></div>
                        <div class="summary-total"><span class="label">Total</span><span class="value" data-summary-total><?= htmlspecialchars($currency . ' ' . number_format($total, 2)) ?></span></div>
                        <p class="summary-note" data-summary-note><?= htmlspecialchars($summaryNote) ?></p>
                    </div>
                    <div class="payment-pick">
                        <span class="payment-pick-label">Choose Payment Method</span>
                        <div class="payment-method-toggle">
                            <button type="button" id="cartPayNowToggleBtn" class="payment-method-toggle-btn pay-now">Pay Now</button>
                            <button type="button" id="cartPayLaterToggleBtn" class="payment-method-toggle-btn pay-later">Pay Later</button>
                        </div>
                        <?php if ($cartHasBlockedItems): ?>
                            <div class="order-flash" style="margin:0;">
                                <strong>Stock notice</strong>
                                One or more items in your cart are out of stock or exceed the available quantity. Please update the cart before proceeding to payment.
                            </div>
                        <?php endif; ?>
                        <div class="payment-choice-grid">
                            <?php
                            $displayModes = is_array($modes) ? $modes : [];
                            $modeOrder = [
                                'payhere' => 10,
                                'bank_transfer' => 20,
                                'whatsapp' => 30,
                                'cod' => 40,
                                'koko' => 50,
                            ];
                            usort($displayModes, static function (array $a, array $b) use ($modeOrder): int {
                                $aKey = (string) ($a['key'] ?? '');
                                $bKey = (string) ($b['key'] ?? '');
                                return ($modeOrder[$aKey] ?? 999) <=> ($modeOrder[$bKey] ?? 999);
                            });
                            ?>
                            <?php foreach ($displayModes as $mode): ?>
                                <button
                                    type="button"
                                    class="payment-method-card <?= htmlspecialchars($mode['key']) ?>"
                                    data-select-mode="<?= htmlspecialchars($mode['key']) ?>"
                                    data-pay-group="<?= in_array($mode['key'], ['payhere', 'bank_transfer'], true) ? 'payNow' : 'payLater' ?>"
                                    <?= $cartHasBlockedItems ? 'disabled aria-disabled="true"' : '' ?>>
                                    <span class="payment-method-icon" aria-hidden="true">
                                        <i class="<?= htmlspecialchars($mode['icon']) ?>"></i>
                                    </span>
                                    <span class="payment-method-copy">
                                        <strong><?= htmlspecialchars($mode['label']) ?></strong>
                                        <small>
                                            <?php
                                            switch ($mode['key']) {
                                                case 'whatsapp':
                                                    echo 'Send your order details directly to the shop on WhatsApp.';
                                                    break;
                                                case 'cod':
                                                    echo 'Place the order now and pay when it is delivered.';
                                                    break;
                                                case 'payhere':
                                                    echo 'Pay online securely before your order is confirmed.';
                                                    break;
                                                case 'koko':
                                                    echo 'Split your payment into 3 interest-free installments.';
                                                    break;
                                                case 'bank_transfer':
                                                    echo 'Place the order now and send the payment using the bank details provided.';
                                                    break;
                                                default:
                                                    echo 'Continue with this payment method.';
                                            }
                                            ?>
                                        </small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right payment-method-arrow" aria-hidden="true"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>
        <?php else: ?>
            <div class="empty-card">
                <h2>Your cart is empty</h2>
                <p>Add products from the shop and they will appear here in the new cart layout with live totals and checkout actions.</p>
                <div class="empty-actions">
                    <a class="btn-action btn-buy" href="<?= htmlspecialchars($baseUrl . 'shop') ?>">Go to Shop</a>
                    <a class="btn-secondary" href="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>">Browse Categories</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

<div class="modal-overlay" id="checkoutModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
        <div class="modal-head">
            <div>
                <h3 id="checkoutTitle">Checkout Details</h3>
                <p>Fill the customer information and place the cart order.</p>
            </div>
            <button type="button" class="modal-close" data-close-checkout-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="modal-form" id="checkoutForm">
            <div class="input-row"><div class="field"><label for="customerName">Customer Name</label><input id="customerName" name="customer_name" required placeholder="Full Name *"></div><div class="field"><label for="customerEmail">Email</label><input id="customerEmail" name="email" type="email" required placeholder="Email Address *"></div></div>
            <div class="field"><label for="customerAddress">Address</label><textarea id="customerAddress" name="address" required placeholder="Address *"></textarea></div>
            <div class="input-row"><div class="field"><label for="customerCity">City</label><input id="customerCity" name="city" required placeholder="City *"></div><div class="field"><label for="customerDistrict">District</label><select id="customerDistrict" name="district" required><?php if (!empty($settings['delivery_apply_all_districts'])): ?><option value="All Districts" <?= $selectedDistrict === 'All Districts' ? 'selected' : '' ?>>All Districts</option><?php endif; ?><?php foreach ($districts as $district): ?><option value="<?= htmlspecialchars($district) ?>" <?= $selectedDistrict === $district ? 'selected' : '' ?>><?= htmlspecialchars($district) ?></option><?php endforeach; ?></select></div></div>
            <div class="input-row"><div class="field"><label for="customerPhone">Phone</label><input id="customerPhone" name="phone" required placeholder="Phone Number 01 *"></div><div class="field"><label for="customerAltPhone">Alt Phone</label><input id="customerAltPhone" name="phone_alt" placeholder="Phone Number 02"></div></div>
            <div class="field"><label for="customerNote">Note</label><textarea id="customerNote" name="note" placeholder="Special Note"></textarea></div>
            <?php if (!empty($settings['bank_transfer_enabled']) && trim((string) ($settings['bank_transfer_details'] ?? '')) !== ''): ?>
                <div id="bankTransferDetailsBox" class="bank-details-box">
                    <strong>Bank Transfer Details</strong>
                    <div class="text"><?= nl2br(htmlspecialchars($settings['bank_transfer_details'])) ?></div>
                </div>
            <?php endif; ?>
            <div class="totals-box">
                <div class="totals-row"><span>Subtotal</span><strong data-modal-subtotal><?= htmlspecialchars($currency . ' ' . number_format($subtotal, 2)) ?></strong></div>
                <div class="totals-row"><span>Shipping Fee</span><strong data-modal-shipping><?= htmlspecialchars($shippingLabel) ?></strong></div>
                <div class="totals-row"><span>Order Total</span><strong data-modal-total><?= htmlspecialchars($currency . ' ' . number_format($total, 2)) ?></strong></div>
            </div>
            <div class="modal-actions"><button type="button" data-close-checkout-modal>Cancel</button><button type="submit" class="primary" id="checkoutSubmit" <?= $cartHasBlockedItems ? 'disabled aria-disabled="true"' : '' ?>>Place Order</button></div>
        </form>
    </div>
</div>

<div class="toast" id="cartToast"><span class="text">Added to basket.</span></div>

</div>

<?php if ($recaptchaCheckoutEnabled && $recaptchaSiteKey !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptchaSiteKey) ?>"></script>
<?php endif; ?>
<script>
(function () {
    const baseUrl = <?= json_encode($baseUrl) ?>;
    const currency = <?= json_encode($currency) ?>;
    const items = <?= json_encode($jsItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const ratesMap = <?= json_encode($ratesMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const settings = <?= json_encode(['apply_all' => !empty($settings['delivery_apply_all_districts']), 'first_kg' => (float)($settings['delivery_all_first_kg'] ?? 0), 'additional_kg' => (float)($settings['delivery_all_additional_kg'] ?? 0)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const whatsappLink = <?= json_encode($whatsappLink) ?>;
    const recaptchaCheckoutEnabled = <?= json_encode($recaptchaCheckoutEnabled) ?>;
    const recaptchaSiteKey = <?= json_encode($recaptchaSiteKey) ?>;
    const modes = <?= json_encode($modes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const whatsappCartItems = <?= json_encode($jsCartWhatsappItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const customerProfileStorageKey = 'style1_customer_order_profile_v1';
    const customerName = document.getElementById('customerName');
    const customerEmail = document.getElementById('customerEmail');
    const customerPhone = document.getElementById('customerPhone');
    const customerAltPhone = document.getElementById('customerAltPhone');
    const customerAddress = document.getElementById('customerAddress');
    const customerCity = document.getElementById('customerCity');
    const customerDistrict = document.getElementById('customerDistrict');
    const customerNote = document.getElementById('customerNote');
    let selectedMode = modes[0] ? modes[0].key : 'cod';

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
        if (customerName && !customerName.value && data.customer_name) customerName.value = data.customer_name;
        if (customerEmail && !customerEmail.value && data.email) customerEmail.value = data.email;
        if (customerPhone && !customerPhone.value && data.phone) customerPhone.value = data.phone;
        if (customerAltPhone && !customerAltPhone.value && data.phone_alt) customerAltPhone.value = data.phone_alt;
        if (customerAddress && !customerAddress.value && data.address) customerAddress.value = data.address;
        if (customerCity && !customerCity.value && data.city) customerCity.value = data.city;
        if (customerNote && !customerNote.value && data.note) customerNote.value = data.note;
        const districtSelect = document.getElementById('customerDistrict');
        if (districtSelect && data.district && !districtSelect.value) districtSelect.value = data.district;
    }
    function captureCustomerProfile() {
        saveCustomerProfile({
            customer_name: customerName ? customerName.value : '',
            email: customerEmail ? customerEmail.value : '',
            phone: customerPhone ? customerPhone.value : '',
            phone_alt: customerAltPhone ? customerAltPhone.value : '',
            address: customerAddress ? customerAddress.value : '',
            city: customerCity ? customerCity.value : '',
            district: customerDistrict ? customerDistrict.value : '',
            note: customerNote ? customerNote.value : ''
        });
    }

    function money(value) { return currency + ' ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function toast(message, type) { const el = document.getElementById('cartToast'); if (!el) return; el.className = 'toast ' + (type || 'success'); el.querySelector('.text').textContent = message; el.classList.add('show'); window.clearTimeout(toast.timer); toast.timer = window.setTimeout(function () { el.classList.remove('show'); }, 2500); }
    function updateGlobalCount(count) { if (typeof window.updateCartUi === 'function') window.updateCartUi(count); }
    function cartHasBlockedItems() { return document.querySelector('[data-cart-line][data-stock-blocked="1"]') !== null; }
    function updatePurchaseActionsState() {
        const blocked = cartHasBlockedItems();
        document.querySelectorAll('[data-select-mode]').forEach(function (button) {
            button.disabled = blocked;
            button.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        });
        const payNowToggleBtn = document.getElementById('cartPayNowToggleBtn');
        const payLaterToggleBtn = document.getElementById('cartPayLaterToggleBtn');
        if (payNowToggleBtn) payNowToggleBtn.disabled = blocked;
        if (payLaterToggleBtn) payLaterToggleBtn.disabled = blocked;
        const submit = document.getElementById('checkoutSubmit');
        if (submit) {
            submit.disabled = blocked;
            submit.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        }
    }
    function setCartPaymentCategory(category) {
        const panel = document.querySelector('.payment-choice-grid');
        if (!panel) return;
        const payNowBtn = document.getElementById('cartPayNowToggleBtn');
        const payLaterBtn = document.getElementById('cartPayLaterToggleBtn');
        panel.querySelectorAll('.payment-method-card[data-pay-group]').forEach(function (card) {
            const group = card.getAttribute('data-pay-group');
            card.style.display = category && group === category ? 'flex' : 'none';
        });
        if (payNowBtn) {
            payNowBtn.classList.toggle('is-active', category === 'payNow');
            payNowBtn.setAttribute('aria-pressed', category === 'payNow' ? 'true' : 'false');
        }
        if (payLaterBtn) {
            payLaterBtn.classList.toggle('is-active', category === 'payLater');
            payLaterBtn.setAttribute('aria-pressed', category === 'payLater' ? 'true' : 'false');
        }
    }
    function recalc() {
        const districtSelect = document.querySelector('[data-district-select]');
        const district = districtSelect ? districtSelect.value : '';
        let subtotal = 0, weight = 0;
        items.forEach(function (item) {
            const qty = Math.max(1, parseInt(item.qty || 1, 10));
            subtotal += qty * Number(item.price || 0);
            if (!item.free && Number(item.weight || 0) > 0) weight += (Number(item.weight || 0) * qty);
        });
        let shipping = 0, hasRate = true;
        if (weight > 0) {
            if (settings.apply_all) {
                shipping = weight <= 1000 ? Number(settings.first_kg || 0) : Number(settings.first_kg || 0) + (Math.ceil((weight - 1000) / 1000) * Number(settings.additional_kg || 0));
            } else {
                const rate = ratesMap[district];
                if (!rate) hasRate = false;
                else shipping = weight <= 1000 ? Number(rate.first_kg_price || 0) : Number(rate.first_kg_price || 0) + (Math.ceil((weight - 1000) / 1000) * Number(rate.additional_kg_price || 0));
            }
        }
        const label = weight <= 0 ? 'Free' : (hasRate ? money(shipping) : 'Select district');
        document.querySelectorAll('[data-summary-subtotal], [data-modal-subtotal]').forEach(function (el) { el.textContent = money(subtotal); });
        document.querySelectorAll('[data-summary-shipping], [data-modal-shipping]').forEach(function (el) { el.textContent = label; });
        document.querySelectorAll('[data-summary-total], [data-modal-total]').forEach(function (el) { el.textContent = money(subtotal + shipping); });
        updatePurchaseActionsState();
    }
    function syncLine(line) { const q = Math.max(1, parseInt(line.dataset.qty || '1', 10)); const p = Number(line.dataset.price || 0); line.querySelector('[data-line-qty]').textContent = String(q); line.querySelector('[data-line-total]').textContent = money(q * p); }
    function updateQty(index, qty, line) { return fetch(baseUrl + 'cart/updateQty', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':(typeof csrfToken !== 'undefined' ? csrfToken : '')}, body:JSON.stringify({ _csrf:(typeof csrfToken !== 'undefined' ? csrfToken : ''), index:index, qty:qty }) }).then(r=>r.json()).then(function (data) { if (!data || !data.success) throw new Error((data && data.message) || 'Unable to update cart'); line.dataset.qty = String(qty); if (items[index]) items[index].qty = qty; syncLine(line); updateGlobalCount(data.count || 0); recalc(); }); }
    function removeLine(index, line) { return fetch(baseUrl + 'cart/remove', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':(typeof csrfToken !== 'undefined' ? csrfToken : '')}, body:JSON.stringify({ _csrf:(typeof csrfToken !== 'undefined' ? csrfToken : ''), index:index }) }).then(r=>r.json()).then(function (data) { if (!data || !data.success) throw new Error((data && data.message) || 'Unable to remove item'); if (index >= 0 && index < items.length) items.splice(index, 1); line.remove(); updateGlobalCount(data.count || 0); recalc(); toast('Cart item removed.', 'success'); if (!document.querySelector('[data-cart-line]')) window.location.reload(); }); }
    function openOverlay(id, show) { const el = document.getElementById(id); if (!el) return; el.style.display = show ? 'flex' : 'none'; el.setAttribute('aria-hidden', show ? 'false' : 'true'); }
    function updateCheckoutModeUi(mode) {
        selectedMode = mode || selectedMode || 'cod';
        const submit = document.getElementById('checkoutSubmit');
        const title = document.getElementById('checkoutTitle');
        const bankBox = document.getElementById('bankTransferDetailsBox');
        if (submit) {
            const map = {
                whatsapp: 'Send via WhatsApp',
                payhere: 'Proceed to Card Payments',
                koko: 'Proceed to KOKO Payments',
                bank_transfer: 'Submit Bank Transfer Order',
                cod: 'Place Order'
            };
            submit.textContent = map[selectedMode] || 'Place Order';
            submit.classList.toggle('checkout-submit-whatsapp', selectedMode === 'whatsapp');
        }
        if (title) {
            const labelMap = {
                whatsapp: 'WhatsApp Order Details',
                payhere: 'Card Payments Details',
                koko: 'KOKO Payments Details',
                bank_transfer: 'Bank Transfer Details',
                cod: 'Checkout Details'
            };
            title.textContent = labelMap[selectedMode] || 'Checkout Details';
        }
        if (bankBox) {
            bankBox.style.display = selectedMode === 'bank_transfer' ? 'block' : 'none';
        }
    }
    function choosePaymentMethod(mode) {
        if (cartHasBlockedItems()) { toast('Remove or update out-of-stock items before checkout.', 'error'); return; }
        updateCheckoutModeUi(mode);
        hydrateCustomerProfile();
        openOverlay('checkoutModal', true);
        recalc();
    }
    async function submitCheckout() {
        const payload = {
            _csrf: (typeof csrfToken !== 'undefined' ? csrfToken : ''),
            customer_name: customerName ? customerName.value.trim() : '',
            email: customerEmail ? customerEmail.value.trim() : '',
            phone: customerPhone ? customerPhone.value.trim() : '',
            phone_alt: customerAltPhone ? customerAltPhone.value.trim() : '',
            address: customerAddress ? customerAddress.value.trim() : '',
            city: customerCity ? customerCity.value.trim() : '',
            district: customerDistrict ? customerDistrict.value.trim() : '',
            note: customerNote ? customerNote.value.trim() : ''
        };
        if (!payload.customer_name || !payload.email || !payload.phone || !payload.address || !payload.city || !payload.district) return toast('Please fill in all required fields.', 'error');
        saveCustomerProfile(payload);
        if (cartHasBlockedItems()) {
            return toast('Remove or update out-of-stock items before checkout.', 'error');
        }
        if (selectedMode === 'whatsapp') {
            if (!whatsappLink) return toast('WhatsApp checkout is not configured.', 'error');
            const summarySubtotalEl = document.querySelector('[data-summary-subtotal]');
            const summaryShippingEl = document.querySelector('[data-summary-shipping]');
            const summaryTotalEl = document.querySelector('[data-summary-total]');
            const productLines = ['\u2713 *Cart Products*', ''];
            (Array.isArray(whatsappCartItems) ? whatsappCartItems : []).forEach(function (item, idx) {
                const qty = Math.max(1, Number(item && item.qty ? item.qty : 1));
                const unitPrice = Math.max(0, Number(item && item.price ? item.price : 0));
                const title = String(item && item.title ? item.title : 'Product').trim() || 'Product';
                const variant = String(item && item.variants ? item.variants : '').trim();
                productLines.push((idx + 1) + '. *' + title + '*');
                if (variant) productLines.push('   \u2022 *Variant:* _' + variant + '_');
                productLines.push('   \u2022 *Qty:* _' + qty + '_');
                productLines.push('   \u2022 *Unit Price:* _' + money(unitPrice) + '_');
                productLines.push('   \u2022 *Line Total:* _' + money(unitPrice * qty) + '_');
                productLines.push('');
            });
            const lines = [
                '\u2728 *Hi, I would like to place a cart order!*',
                '',
                ...productLines,
                '',
                '\u2713 *Delivery Details*',
                '\u2022 *Full Name:* _' + payload.customer_name + '_',
                '\u2022 *Email Address:* _' + payload.email + '_',
                '\u2022 *Address:* _' + payload.address + '_',
                '\u2022 *City:* _' + payload.city + '_',
                '\u2022 *District:* _' + payload.district + '_',
                '\u2022 *Phone Number 1:* _' + payload.phone + '_',
                '\u2022 *Phone Number 2:* _' + (payload.phone_alt || 'N/A') + '_',
                '\u2022 *Special Note:* _' + (payload.note || 'N/A') + '_',
                '',
                '',
                '\u2713 *Order Summary*',
                '\u2022 *Subtotal:* _' + (summarySubtotalEl ? summarySubtotalEl.textContent : '') + '_',
                '\u2022 *Delivery Fee:* _' + (summaryShippingEl ? summaryShippingEl.textContent : '') + '_',
                '\u2022 *Order Total:* _' + (summaryTotalEl ? summaryTotalEl.textContent : '') + '_',
                '',
                '\uD83D\uDE4F _Thank you!_'
            ];
            window.open(whatsappLink + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
            openOverlay('checkoutModal', false);
            return;
        }
        try {
            if (recaptchaCheckoutEnabled) {
                payload.g_recaptcha_response = await getRecaptchaToken();
                payload.g_recaptcha_action = 'checkout_order';
            }
        } catch (error) {
            return toast(error && error.message ? error.message : 'Unable to verify checkout.', 'error');
        }
        const routeMap = { cod:'order/startCod', payhere:'order/startPayhere', koko:'order/startKoko', bank_transfer:'order/startBankTransfer' };
        const form = document.createElement('form');
        form.method = 'POST'; form.action = baseUrl + (routeMap[selectedMode] || routeMap.cod);
        Object.keys(payload).forEach(function (key) { const input = document.createElement('input'); input.type = 'hidden'; input.name = key; input.value = payload[key]; form.appendChild(input); });
        document.body.appendChild(form); form.submit();
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-qty-minus],[data-qty-plus],[data-cart-remove],[data-cart-clear],[data-close-checkout-modal],[data-select-mode],#cartPayNowToggleBtn,#cartPayLaterToggleBtn');
        if (!btn) return;
        const line = btn.closest('[data-cart-line]');
        const index = line ? parseInt(line.dataset.index || '0', 10) : 0;
        if (btn.matches('[data-qty-minus]')) { updateQty(index, Math.max(1, parseInt(line.dataset.qty || '1', 10) - 1), line).catch(e=>toast(e.message, 'error')); }
        if (btn.matches('[data-qty-plus]')) { updateQty(index, Math.max(1, parseInt(line.dataset.qty || '1', 10) + 1), line).catch(e=>toast(e.message, 'error')); }
        if (btn.matches('[data-cart-remove]')) { removeLine(index, line).catch(e=>toast(e.message, 'error')); }
        if (btn.matches('[data-cart-clear]')) { event.preventDefault(); fetch(baseUrl + 'cart/clear', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':(typeof csrfToken !== 'undefined' ? csrfToken : '')}, body:JSON.stringify({ _csrf:(typeof csrfToken !== 'undefined' ? csrfToken : '') }) }).then(r=>r.json()).then(function (data) { if (!data || !data.success) throw new Error('Unable to clear cart'); updateGlobalCount(0); window.location.reload(); }).catch(e=>toast(e.message, 'error')); }
        if (btn.matches('[data-close-checkout-modal]')) { openOverlay('checkoutModal', false); }
        if (btn.matches('#cartPayNowToggleBtn')) { setCartPaymentCategory('payNow'); }
        if (btn.matches('#cartPayLaterToggleBtn')) { setCartPaymentCategory('payLater'); }
        if (btn.matches('[data-select-mode]')) { choosePaymentMethod(btn.getAttribute('data-select-mode')); }
    });

    document.addEventListener('change', function (event) {
        if (event.target && event.target.matches('[data-district-select]')) {
            const modalDistrict = document.getElementById('customerDistrict');
            if (modalDistrict) modalDistrict.value = event.target.value;
            recalc();
        }
        if (event.target && event.target.id === 'customerDistrict') {
            const districtSelect = document.querySelector('[data-district-select]');
            if (districtSelect) districtSelect.value = event.target.value;
            recalc();
        }
    });
    document.addEventListener('input', function (event) {
        if (event.target && event.target.closest && event.target.closest('#checkoutForm')) captureCustomerProfile();
    });

    document.addEventListener('submit', function (event) {
        if (event.target && event.target.id === 'checkoutForm') { event.preventDefault(); submitCheckout(); }
    });

    document.querySelectorAll('[data-cart-line]').forEach(function (line) {
        syncLine(line);
    });
    hydrateCustomerProfile();
    recalc();
    setCartPaymentCategory('');
    updateCheckoutModeUi(selectedMode);
    if (typeof window.refreshCartUi === 'function') window.refreshCartUi();
})();
</script>

<?php customer_layout_end(); ?>
