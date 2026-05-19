<?php
if (!function_exists('customer_layout_start')) {
    if (!function_exists('customer_layout_settings')) {
        function customer_layout_settings(): array
        {
            global $settings;

            if (!is_array($settings)) {
                $settings = [];
            }

            if (!isset($settings['shop_logo']) || !isset($settings['shop_name']) || $settings['shop_logo'] === '' || $settings['shop_name'] === '') {
                if (!class_exists('Setting')) {
                    require_once ROOT_PATH . 'models/Setting.php';
                }

                try {
                    $settingModel = new Setting();
                    $dbSettings = $settingModel->getAllPairs();
                    if (is_array($dbSettings)) {
                        $settings = array_merge($dbSettings, $settings);
                    }
                } catch (Throwable $e) {
                    // Keep any settings already available from the controller.
                }
            }

            return $settings;
        }
    }

    if (!function_exists('customer_layout_cart_count')) {
        function customer_layout_cart_count(): int
        {
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                return 0;
            }

            $count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $count += max(1, (int) ($item['qty'] ?? 0));
            }

            return max(0, $count);
        }
    }

    function customer_layout_start(array $options = []): void
    {
        global $settings, $title, $seo_title, $seo_description, $seo_image, $seo_canonical, $seo_robots, $seo_type, $seo_json_ld;

        require_once 'helpers/SeoHelper.php';
        require_once ROOT_PATH . 'helpers/ImageHelper.php';
        require_once ROOT_PATH . 'helpers/FooterHelper.php';
        require_once ROOT_PATH . 'helpers/RecaptchaHelper.php';

        $settings = customer_layout_settings();
        $customerCssVersion = @filemtime(ROOT_PATH . 'assets/css/customer.css') ?: time();
        $desktopCssVersion = @filemtime(ROOT_PATH . 'assets/css/customer-desktop-refresh.css') ?: time();
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
        $metaTitle = isset($seo_title) ? $seo_title : (isset($title) ? $title : SeoHelper::shopName($settings));
        $metaDescription = isset($seo_description) ? $seo_description : ($settings['shop_about'] ?? '');
        $metaImage = isset($seo_image) ? $seo_image : ($settings['shop_logo'] ?? '');
        $metaImage = SeoHelper::normalizeAssetUrl($metaImage);
        $metaUrl = isset($seo_canonical) ? $seo_canonical : SeoHelper::currentUrl(false);
        $metaRobots = isset($seo_robots) ? $seo_robots : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
        $metaType = isset($seo_type) && $seo_type === 'product' ? 'product' : 'website';
        $siteLogoUrl = ImageHelper::settingsImageUrl(
            (string) ($settings['shop_logo'] ?? ''),
            'assets/uploads/1774110158_logo_logo.jpg'
        );
        $supportEmail = trim((string) ($settings['smtp_from_email'] ?? ''));
        if ($supportEmail === '') {
            $supportEmail = trim((string) ($settings['shop_email'] ?? ''));
        }
        $mobileNavEmail = trim((string) ($settings['shop_owner_email'] ?? ''));
        if ($mobileNavEmail === '') {
            $mobileNavEmail = trim((string) ($settings['shop_email'] ?? ''));
        }
        $whatsappLink = FooterHelper::whatsappMessageLink($settings, 'Hi, I need help with my order.');
        $whatsappLabel = FooterHelper::whatsappLabel($settings);
        $shopWhatsappDigits = FooterHelper::whatsappDigits($settings);
        $cartCount = customer_layout_cart_count();
        $layoutRoute = trim((string) ($_GET['url'] ?? ''), '/');
        if ($layoutRoute === '') {
            $layoutRoute = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        }
        $layoutRoute = strtolower($layoutRoute);
        $isHomeRoute = ($layoutRoute === '' || $layoutRoute === 'home' || $layoutRoute === 'home2' || $layoutRoute === 'index.php');
        $headerDesktopBgUrl = $baseUrl . 'assets/header/header-pc.jpg?v=' . (@filemtime(ROOT_PATH . 'assets/header/header-pc.jpg') ?: time());
        $headerMobileBgUrl = $baseUrl . 'assets/header/header-Mobile.jpg?v=' . (@filemtime(ROOT_PATH . 'assets/header/header-Mobile.jpg') ?: time());
        $currentRoute = trim((string) ($_GET['url'] ?? ''), '/');
        if ($currentRoute === '') {
            $currentRoute = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        }
        if ($currentRoute === 'index.php' || $currentRoute === 'home' || $currentRoute === 'home2') {
            $currentRoute = '';
        }

        $goldPrimary = '#b68a2d';
        $goldAccent = '#d4af37';
        $goldLegacy = static function (string $value, string $fallback) use ($goldPrimary, $goldAccent): string {
            $normalized = strtolower(trim($value));
            $legacyPrimaryReds = [
                '',
                '#b9000b',
                '#b9000bff',
                '#9747ff',
                '#a64b2a',
                '#8f3d16',
                '#8a431f',
            ];
            $legacyAccentReds = [
                '#e31a1a',
                '#ff3b30',
                '#d95d4f',
                '#bb3d33',
            ];

            if (in_array($normalized, $legacyPrimaryReds, true)) {
                return $fallback === '#d4af37' ? $goldAccent : $goldPrimary;
            }
            if (in_array($normalized, $legacyAccentReds, true)) {
                return $goldAccent;
            }
            if (preg_match('/^#?([0-9a-f]{6})$/', $normalized, $match)) {
                $hex = $match[1];
                $red = hexdec(substr($hex, 0, 2));
                $green = hexdec(substr($hex, 2, 2));
                $blue = hexdec(substr($hex, 4, 2));
                if ($red >= 120 && $green <= 120 && $blue <= 120 && $red > ($green + 35)) {
                    return $fallback === '#d4af37' ? $goldAccent : $goldPrimary;
                }
            }
            return $value;
        };

        $sitePrimary = $goldLegacy(trim((string) ($settings['primary_color'] ?? $goldPrimary)), $goldPrimary);
        $siteSecondary = trim((string) ($settings['secondary_color'] ?? '#1f1f1f')) ?: '#1f1f1f';
        $siteAccentRed = $goldLegacy(trim((string) ($settings['accent_red'] ?? $goldAccent)), $goldAccent);
        $siteBg = trim((string) ($settings['bg_color'] ?? '#ffffff')) ?: '#ffffff';
        $siteSurface = trim((string) ($settings['surface_color'] ?? '#fafafa')) ?: '#fafafa';
        $siteInk = trim((string) ($settings['ink_color'] ?? '#1c1b1b')) ?: '#1c1b1b';
        $siteMuted = trim((string) ($settings['muted_color'] ?? '#6d6665')) ?: '#6d6665';
        $siteBorder = trim((string) ($settings['border_color'] ?? '#eceaea')) ?: '#eceaea';
        $headerBg = trim((string) ($settings['header_bg'] ?? '#ffffff')) ?: '#ffffff';
        $headerText = trim((string) ($settings['header_text'] ?? '#1c1b1b')) ?: '#1c1b1b';
        $footerBg = trim((string) ($settings['footer_bg'] ?? '#ffffff')) ?: '#ffffff';
        $footerText = trim((string) ($settings['footer_text'] ?? '#1c1b1b')) ?: '#1c1b1b';
        $footerLink = $goldLegacy(trim((string) ($settings['footer_link'] ?? $goldPrimary)), $goldPrimary);
        $navMobileBg = trim((string) ($settings['nav_mobile_bg'] ?? '#ffffff')) ?: '#ffffff';
        $navMobileIcon = trim((string) ($settings['nav_mobile_icon_color'] ?? '#999999')) ?: '#999999';
        $navMobileActive = $goldLegacy(trim((string) ($settings['nav_mobile_active_color'] ?? $goldPrimary)), $goldPrimary);
        $navDesktopBg = trim((string) ($settings['nav_desktop_bg'] ?? '#ffffff')) ?: '#ffffff';
        $navDesktopLink = trim((string) ($settings['nav_desktop_link_color'] ?? '#666666')) ?: '#666666';
        $floatingCartBg = $goldLegacy(trim((string) ($settings['floating_cart_bg'] ?? $goldPrimary)), $goldPrimary);
        $floatingCartText = trim((string) ($settings['floating_cart_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnAddCartBg = trim((string) ($settings['btn_addcart_bg'] ?? '#111111')) ?: '#111111';
        $btnAddCartText = trim((string) ($settings['btn_addcart_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnOrderNowBg = $goldLegacy(trim((string) ($settings['btn_ordernow_bg'] ?? $goldPrimary)), $goldPrimary);
        $btnOrderNowText = trim((string) ($settings['btn_ordernow_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnCartWhatsappBg = trim((string) ($settings['btn_cart_whatsapp_bg'] ?? '#25d366')) ?: '#25d366';
        $btnCartWhatsappText = trim((string) ($settings['btn_cart_whatsapp_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnCartCodBg = trim((string) ($settings['btn_cart_cod_bg'] ?? '#111111')) ?: '#111111';
        $btnCartCodText = trim((string) ($settings['btn_cart_cod_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnCartPayhereBg = trim((string) ($settings['btn_cart_payhere_bg'] ?? '#111111')) ?: '#111111';
        $btnCartPayhereText = trim((string) ($settings['btn_cart_payhere_text'] ?? '#ffffff')) ?: '#ffffff';
        $btnCartKokoBg = trim((string) ($settings['btn_cart_koko_bg'] ?? '#fff3dc')) ?: '#fff3dc';
        $btnCartKokoText = trim((string) ($settings['btn_cart_koko_text'] ?? '#111111')) ?: '#111111';
        $promoEnabled = !empty($settings['promo_enabled']);
        $promoImageUrl = '';
        $promoLink = trim((string) ($settings['promo_link'] ?? ''));
        $promoOpenNewTab = !isset($settings['promo_open_new_tab'])
            || $settings['promo_open_new_tab'] === ''
            || !empty($settings['promo_open_new_tab']);
        if ($promoEnabled) {
            $promoImageUrl = ImageHelper::settingsImageUrl((string) ($settings['promo_image'] ?? ''), '');
            if ($promoImageUrl === '') {
                $promoEnabled = false;
            }
        }
        if ($promoLink !== '' && !preg_match('#^https?://#i', $promoLink) && strpos($promoLink, '/') !== 0) {
            $promoLink = 'https://' . $promoLink;
        }
        $promoStateKey = hash('sha256', $promoImageUrl . '|' . $promoLink);

        $menuItems = [
            [
                'label' => 'Home',
                'url' => $baseUrl,
                'active' => ($currentRoute === ''),
            ],
            [
                'label' => 'Shop',
                'url' => $baseUrl . 'shop',
                'active' => (strpos($currentRoute, 'shop') === 0),
            ],
            [
                'label' => 'Categories',
                'url' => $baseUrl . 'shop/categories',
                'active' => (strpos($currentRoute, 'shop/categories') === 0),
            ],
            [
                'label' => 'Contact Us',
                'url' => $baseUrl . 'contact',
                'active' => (strpos($currentRoute, 'contact') === 0),
            ],
        ];

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="<?= htmlspecialchars($metaRobots) ?>">
    <meta name="googlebot" content="<?= htmlspecialchars($metaRobots) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($metaUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($settings['shop_name'] ?? 'STYLE1') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($metaType) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($metaUrl) ?>">
    <meta property="og:locale" content="en_LK">
    <?php if (!empty($metaDescription)): ?><meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if (!empty($metaImage)): ?><meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>"><?php endif; ?>
    <?php if (!empty($metaTitle) && !empty($metaImage)): ?><meta property="og:image:alt" content="<?= htmlspecialchars($metaTitle) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta name="twitter:url" content="<?= htmlspecialchars($metaUrl) ?>">
    <?php if (!empty($metaDescription)): ?><meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if (!empty($metaImage)): ?><meta name="twitter:image" content="<?= htmlspecialchars($metaImage) ?>"><?php endif; ?>
    <?php if (!empty($seo_json_ld) && is_array($seo_json_ld)): ?>
        <?php foreach ($seo_json_ld as $schema): ?>
            <?php if (!empty($schema)): ?>
                <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($settings['shop_favicon'])): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(ImageHelper::settingsImageUrl($settings['shop_favicon'], str_replace('/Ecom-CMS/', BASE_URL, $settings['shop_favicon']))) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/customer.css?v=<?= $customerCssVersion ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/customer-desktop-refresh.css?v=<?= $desktopCssVersion ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
        :root{
            --surface:<?= htmlspecialchars($siteBg) ?>;
            --surface-soft:<?= htmlspecialchars($siteSurface) ?>;
            --ink:<?= htmlspecialchars($siteInk) ?>;
            --muted:<?= htmlspecialchars($siteMuted) ?>;
            --line:<?= htmlspecialchars($siteBorder) ?>;
            --accent:<?= htmlspecialchars($sitePrimary) ?>;
            --primary:<?= htmlspecialchars($sitePrimary) ?>;
            --primary-color:<?= htmlspecialchars($sitePrimary) ?>;
            --accent-color:<?= htmlspecialchars($sitePrimary) ?>;
            --primary-strong:<?= htmlspecialchars($siteAccentRed) ?>;
            --secondary:<?= htmlspecialchars($siteSecondary) ?>;
            --accent-red:<?= htmlspecialchars($siteAccentRed) ?>;
            --header-bg:<?= htmlspecialchars($headerBg) ?>;
            --header-text:<?= htmlspecialchars($headerText) ?>;
            --footer-bg:<?= htmlspecialchars($footerBg) ?>;
            --footer-text:<?= htmlspecialchars($footerText) ?>;
            --footer-link:<?= htmlspecialchars($footerLink) ?>;
            --nav-mobile-bg:<?= htmlspecialchars($navMobileBg) ?>;
            --nav-mobile-icon:<?= htmlspecialchars($navMobileIcon) ?>;
            --nav-mobile-active:<?= htmlspecialchars($navMobileActive) ?>;
            --nav-desktop-bg:<?= htmlspecialchars($navDesktopBg) ?>;
            --nav-desktop-link:<?= htmlspecialchars($navDesktopLink) ?>;
            --floating-cart-bg:<?= htmlspecialchars($floatingCartBg) ?>;
            --floating-cart-text:<?= htmlspecialchars($floatingCartText) ?>;
            --btn-addcart-bg:<?= htmlspecialchars($btnAddCartBg) ?>;
            --btn-addcart-text:<?= htmlspecialchars($btnAddCartText) ?>;
            --btn-ordernow-bg:<?= htmlspecialchars($btnOrderNowBg) ?>;
            --btn-ordernow-text:<?= htmlspecialchars($btnOrderNowText) ?>;
            --btn-cart-whatsapp-bg:<?= htmlspecialchars($btnCartWhatsappBg) ?>;
            --btn-cart-whatsapp-text:<?= htmlspecialchars($btnCartWhatsappText) ?>;
            --btn-cart-cod-bg:<?= htmlspecialchars($btnCartCodBg) ?>;
            --btn-cart-cod-text:<?= htmlspecialchars($btnCartCodText) ?>;
            --btn-cart-payhere-bg:<?= htmlspecialchars($btnCartPayhereBg) ?>;
            --btn-cart-payhere-text:<?= htmlspecialchars($btnCartPayhereText) ?>;
            --btn-cart-koko-bg:<?= htmlspecialchars($btnCartKokoBg) ?>;
            --btn-cart-koko-text:<?= htmlspecialchars($btnCartKokoText) ?>;
            --shadow:0 10px 28px rgba(31,31,31,.05);
        }
        *,*::before,*::after{box-sizing:border-box;border-radius:0 !important}
        html,body{margin:0;background:var(--surface);color:var(--ink)}
        body{font-family:"Manrope",sans-serif}
        h1,h2,h3,h4,h5{font-family:"Noto Serif",serif;font-weight:400}
        a{color:inherit;text-decoration:none}
        img{display:block;max-width:100%}
        .page{display:flex;flex-direction:column;min-height:100vh;background:var(--surface);overflow-x:hidden}
        .site-header{
            position:sticky;
            top:0;
            z-index:60;
            background-color:var(--header-bg);
            background-image:linear-gradient(rgba(0,0,0,.28), rgba(0,0,0,.28)), url('<?= htmlspecialchars($headerDesktopBgUrl, ENT_QUOTES, 'UTF-8') ?>');
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
            backdrop-filter:saturate(180%) blur(12px);
            border-bottom:none;
            color:var(--header-text);
        }
        .site-header-inner{
            width:min(1600px,calc(100% - 80px));
            height:88px;
            margin:0 auto;
            display:grid;
            grid-template-columns:auto 1fr auto;
            align-items:center;
            gap:24px;
        }
        .site-brand{
            display:inline-flex;
            align-items:center;
            justify-content:flex-start;
            min-width:0;
        }
        .site-brand img{
            width:auto;
            height:auto;
            max-height:68px;
            max-width:280px;
            object-fit:contain;
        }
        .site-nav{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:30px;
            min-width:0;
            font-size:11px;
            letter-spacing:.24em;
            text-transform:uppercase;
            white-space:nowrap;
        }
        .site-nav a{
            color:#ffffff;
            padding:10px 0 11px;
            border-bottom:1px solid transparent;
            transition:color .2s ease,border-color .2s ease;
        }
        .site-nav a:hover,
        .site-nav a.active{
            color:#ffffff;
            border-bottom-color:#ffffff;
        }
        .site-actions{
            display:flex;
            align-items:center;
            gap:10px;
        }
        .site-action{
            width:40px;
            height:40px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid var(--line);
            background:var(--nav-desktop-bg);
            color:var(--header-text);
            box-shadow:var(--shadow);
            transition:transform .2s ease,color .2s ease,border-color .2s ease;
            position:relative;
        }
        .site-action:hover{transform:translateY(-1px);color:var(--accent);border-color:rgba(194,0,16,.18)}
        .site-action svg{
            width:17px;
            height:17px;
            fill:none;
            stroke:currentColor;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .site-action-badge{
            position:absolute;
            top:-6px;
            right:-6px;
            min-width:18px;
            height:18px;
            padding:0 5px;
            border-radius:999px;
            background:#d4af37;
            color:#fff;
            font-size:10px;
            font-weight:800;
            line-height:18px;
            text-align:center;
            box-shadow:0 6px 16px rgba(212,175,55,.35);
        }
        .mobile-menu-toggle{
            display:none;
            width:40px;
            height:40px;
            border:1px solid var(--line);
            background:var(--nav-desktop-bg);
            box-shadow:var(--shadow);
            align-items:center;
            justify-content:center;
            padding:0;
            cursor:pointer;
        }
        .mobile-menu-toggle .bars{display:flex;flex-direction:column;gap:4px}
        .mobile-menu-toggle .bars span{display:block;width:18px;height:2px;background:var(--header-text)}
        .mobile-nav-toggle-state{
            position:absolute;
            width:1px;
            height:1px;
            opacity:0;
            pointer-events:none;
        }
        .mobile-nav-panel{
            display:none;
            border-top:1px solid var(--line);
            background:var(--nav-mobile-bg);
        }
        .mobile-nav-panel.open{display:block}
        .site-search-overlay{
            position:fixed;
            inset:0;
            z-index:110;
            background:rgba(10,10,10,.52);
            display:none;
            align-items:flex-start;
            justify-content:center;
            padding:92px 16px 24px;
            overflow:auto;
        }
        .site-search-overlay.open{
            display:flex;
        }
        .site-search-modal{
            width:min(920px,100%);
            background:#fff;
            border:1px solid rgba(28,27,27,.08);
            box-shadow:0 26px 56px rgba(28,27,27,.22);
            padding:18px;
            display:grid;
            gap:14px;
        }
        .site-search-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }
        .site-search-kicker{
            font-size:10px;
            font-weight:800;
            letter-spacing:.2em;
            text-transform:uppercase;
            color:var(--primary);
        }
        .site-search-close{
            width:36px;
            height:36px;
            border:1px solid rgba(28,27,27,.12);
            background:#fff;
            color:var(--ink);
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        .site-search-form{
            display:grid;
            gap:12px;
        }
        .site-search-row{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:10px;
        }
        .site-search-input{
            width:100%;
            min-height:50px;
            border:1px solid rgba(28,27,27,.14);
            background:#fff;
            color:var(--ink);
            padding:0 14px;
            font-size:15px;
            outline:none;
        }
        .site-search-input:focus{
            border-color:rgba(182,138,45,.5);
            box-shadow:0 0 0 3px rgba(182,138,45,.12);
        }
        .site-search-submit{
            min-height:50px;
            border:0;
            background:var(--ink);
            color:#fff;
            padding:0 20px;
            font-size:11px;
            font-weight:800;
            letter-spacing:.2em;
            text-transform:uppercase;
            cursor:pointer;
        }
        .site-search-meta{
            font-size:12px;
            color:var(--muted);
        }
        .site-search-suggestions{
            border:1px solid rgba(28,27,27,.08);
            background:#fff;
            max-height:340px;
            overflow:auto;
            display:none;
        }
        .site-search-suggestions.open{
            display:block;
        }
        .site-search-suggestion{
            display:grid;
            grid-template-columns:52px minmax(0,1fr);
            gap:10px;
            align-items:center;
            width:100%;
            border:0;
            border-bottom:1px solid rgba(28,27,27,.06);
            background:#fff;
            color:var(--ink);
            text-align:left;
            padding:10px 12px;
            cursor:pointer;
        }
        .site-search-suggestion:last-child{
            border-bottom:0;
        }
        .site-search-suggestion.active,
        .site-search-suggestion:hover{
            background:#f8f6f4;
        }
        .site-search-suggestion-thumb{
            width:52px;
            height:52px;
            object-fit:cover;
            background:#f0eded;
        }
        .site-search-suggestion-title{
            font-size:14px;
            font-weight:700;
            line-height:1.35;
            display:block;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .site-search-suggestion-meta{
            font-size:11px;
            color:var(--muted);
            letter-spacing:.08em;
            text-transform:uppercase;
            margin-top:2px;
            display:block;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .mobile-nav-panel-head{
            width:min(1600px,calc(100% - 28px));
            margin:0 auto;
            padding:14px 0 12px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            border-bottom:1px solid var(--line);
        }
        .mobile-nav-panel-title{
            font-size:10px;
            letter-spacing:.24em;
            text-transform:uppercase;
            color:var(--nav-mobile-active);
            font-weight:700;
        }
        .mobile-nav-close{
            width:32px;
            height:32px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid var(--line);
            background:#fff;
            color:var(--ink);
            box-shadow:var(--shadow);
            cursor:pointer;
            padding:0;
        }
        .mobile-nav-close i{
            font-size:14px;
            line-height:1;
        }
        .mobile-nav-links{
            width:min(1600px,calc(100% - 28px));
            margin:0 auto;
            padding:14px 0 18px;
            display:grid;
            gap:8px;
        }
        .mobile-nav-contact{
            width:min(1600px,calc(100% - 28px));
            margin:0 auto;
            padding:0 0 18px;
            display:grid;
            gap:10px;
            border-top:1px solid var(--line);
        }
        .mobile-nav-contact-head{
            padding-top:14px;
            font-size:10px;
            letter-spacing:.24em;
            text-transform:uppercase;
            color:var(--nav-mobile-active);
            font-weight:700;
        }
        .mobile-nav-contact-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:12px 0;
            border-bottom:1px solid var(--line);
            color:var(--header-text);
            font-size:12px;
            letter-spacing:.12em;
            text-transform:uppercase;
        }
        .mobile-nav-contact-item i{
            width:18px;
            text-align:center;
            color:var(--nav-mobile-active);
            font-size:15px;
        }
        .mobile-nav-contact-item span{
            word-break:break-word;
        }
        .mobile-nav-contact-item.whatsapp-btn{
            background:#25d366;
            color:#fff;
            border:0;
            padding:12px 14px;
            box-shadow:0 10px 24px rgba(37,211,102,.24);
            font-weight:700;
        }
        .mobile-nav-contact-item.whatsapp-btn i{
            color:#fff;
        }
        .mobile-nav-contact-item.whatsapp-btn:hover{
            background:#20bd5a;
        }
        .mobile-nav-links a{
            padding:12px 0;
            font-size:12px;
            letter-spacing:.2em;
            text-transform:uppercase;
            color:var(--header-text);
            border-bottom:1px solid var(--line);
        }
        .site-content{
            flex:1 0 auto;
            padding-top:0;
        }
        .site-promo-popup{
            position:fixed;
            top:92px;
            right:18px;
            z-index:1300;
            width:min(34vw, 220px);
            min-width:130px;
            max-width:220px;
        }
        .site-promo-popup[hidden]{
            display:none !important;
        }
        .site-promo-popup-inner{
            position:relative;
            display:block;
            border-radius:10px;
            overflow:hidden;
            box-shadow:none;
            background:transparent;
        }
        .site-promo-popup-image{
            display:block;
            width:100%;
            height:auto;
        }
        .site-promo-popup-close{
            position:absolute;
            top:6px;
            right:6px;
            width:24px;
            height:24px;
            border:0;
            border-radius:999px;
            background:rgba(17,17,17,.78);
            color:#fff;
            font-size:16px;
            line-height:1;
            display:grid;
            place-items:center;
            cursor:pointer;
            padding:0;
        }
        .site-promo-popup-close:hover{
            background:rgba(17,17,17,.92);
        }
        .site-footer{
            background:var(--footer-bg);
            border-top:1px solid var(--line);
            padding:72px 0 28px;
            color:var(--footer-text);
        }
        .site-footer-shell{
            width:min(1600px,calc(100% - 80px));
            margin:0 auto;
            background:var(--footer-bg);
            padding:34px 36px 24px;
        }
        .site-footer-top-badge-image{
            margin:-25px 0 50px;
            text-align:center;
        }
        .site-footer-top-badge-image img{
            display:block;
            width:min(100%, 560px);
            height:auto;
            margin:0 auto;
        }
        .site-footer-grid{
            display:grid;
            grid-template-columns:minmax(0,1.25fr) minmax(180px,.7fr) minmax(180px,.7fr) minmax(260px,1fr);
            gap:36px;
            align-items:start;
        }
        .site-footer-brand,
        .site-footer-column,
        .site-footer-payments{
            min-width:0;
        }
        .site-footer-eyebrow{
            display:block;
            margin-bottom:14px;
            font-size:10px;
            letter-spacing:.24em;
            text-transform:uppercase;
            color:var(--footer-link);
            font-weight:700;
        }
        .site-footer-brand .site-footer-eyebrow{
            color:#000;
        }
        .site-footer-brand-title{
            margin:0 0 12px;
            font-family:"Noto Serif",serif;
            font-size:clamp(26px,2.2vw,42px);
            line-height:1.05;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:var(--footer-text);
        }
        .site-footer-slogan{
            margin:0 0 16px;
            max-width:32ch;
            color:color-mix(in srgb, var(--footer-text) 60%, transparent);
            font-size:12px;
            line-height:1.85;
            letter-spacing:.2em;
            text-transform:uppercase;
        }
        .site-footer-copy{
            margin:0;
            max-width:42ch;
            color:color-mix(in srgb, var(--footer-text) 72%, transparent);
            font-size:14px;
            line-height:1.9;
        }
        .site-footer-links{
            display:grid;
            gap:12px;
        }
        .site-footer-links a{
            color:color-mix(in srgb, var(--footer-text) 72%, transparent);
            font-size:12px;
            letter-spacing:.08em;
            text-transform:uppercase;
            transition:color .2s ease;
        }
        .site-footer-links a:hover{
            color:var(--footer-link);
        }
        .site-footer-note{
            margin:0 0 16px;
            color:color-mix(in srgb, var(--footer-text) 60%, transparent);
            font-size:14px;
            line-height:1.8;
            max-width:42ch;
        }
        .site-footer-payment-grid{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
        }
        .site-footer-payment-card{
            display:flex;
            align-items:center;
            justify-content:center;
            width:156px;
            min-height:48px;
            padding:10px 14px;
            border:1px solid var(--line);
            background:var(--footer-bg);
            box-shadow:0 6px 16px rgba(31,31,31,.04);
        }
        .site-footer-payment-card img{
            width:auto;
            max-width:100%;
            max-height:24px;
            object-fit:contain;
        }
        .site-footer-empty{
            padding:12px 0;
            color:color-mix(in srgb, var(--footer-text) 60%, transparent);
            font-size:12px;
            letter-spacing:.16em;
            text-transform:uppercase;
        }
        .site-footer-bottom{
            margin-top:24px;
            padding-top:18px;
            border-top:1px solid var(--line);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
        }
        .site-footer-copyright{
            font-size:11px;
            letter-spacing:.2em;
            text-transform:uppercase;
            color:color-mix(in srgb, var(--footer-text) 58%, transparent);
        }
        .site-footer-copyright a{
            color:var(--footer-link);
            text-decoration:none;
            transition:color .2s ease;
        }
        .site-footer-copyright a:hover{
            color:var(--footer-link);
        }
        .site-footer-badges{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .site-footer-badges span{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:30px;
            padding:0 12px;
            border:1px solid var(--line);
            background:var(--footer-bg);
            color:color-mix(in srgb, var(--footer-text) 65%, transparent);
            font-size:10px;
            letter-spacing:.18em;
            text-transform:uppercase;
        }
        .global-loader-overlay{display:none}
        @media (max-width: 1024px){
            .site-header-inner{
                width:min(100% - 40px,1600px);
                height:76px;
                gap:16px;
            }
            .site-nav{gap:20px;font-size:10px}
            .site-brand img{max-height:50px;max-width:210px}
            .site-footer{
                padding:56px 0 22px;
            }
            .site-footer-shell{
                width:min(100% - 40px,1600px);
                padding:28px 26px 22px;
            }
            .site-footer-grid{
                grid-template-columns:repeat(2,minmax(0,1fr));
                gap:28px 24px;
            }
        }
        @media (max-width: 760px){
            .site-promo-popup{
                top:82px;
                right:10px;
                width:min(44vw, 180px);
                min-width:110px;
            }
            body.is-non-home-page{
                padding-left:0 !important;
                padding-right:0 !important;
            }
            .site-header{
                overflow:visible;
                background-image:linear-gradient(rgba(0,0,0,.28), rgba(0,0,0,.28)), url('<?= htmlspecialchars($headerMobileBgUrl, ENT_QUOTES, 'UTF-8') ?>');
            }
            #mobileNavToggleState:checked ~ .mobile-nav-panel{
                transform:translateX(0);
                opacity:1;
                pointer-events:auto;
            }
            .site-header-inner{
                width:100%;
                height:76px;
                grid-template-columns:minmax(0,1fr) auto;
                gap:12px;
                padding-left:12px;
                padding-right:12px;
            }
            .site-brand{
                width:100%;
            }
            .site-brand img{
                max-height:68px;
                max-width:100%;
                width:100%;
                object-position:left center;
            }
            .site-nav{display:none}
            .site-actions{gap:10px;justify-content:flex-end}
            .site-action[data-cart-link]{display:none}
            .site-action{
                width:40px;
                height:40px;
            }
            .site-action.no-loader[data-header-search],
            .mobile-menu-toggle{
                background:linear-gradient(135deg,#b68a2d 0%,#d4af37 56%,#a8791d 100%);
                border-color:rgba(255,255,255,.35);
                box-shadow:0 8px 18px rgba(182,138,45,.32);
            }
            .site-action.no-loader[data-header-search]{
                color:#fff;
            }
            .site-action svg{width:19px;height:19px}
            .mobile-menu-toggle{display:inline-flex;width:40px;height:40px}
            .mobile-menu-toggle .bars{gap:4px}
            .mobile-menu-toggle .bars span{width:19px;background:#fff}
            .mobile-nav-panel{
                display:block;
                position:fixed;
                top:76px;
                left:0;
                width:min(82vw,320px);
                height:calc(100vh - 76px);
                transform:translateX(-100%);
                opacity:0;
                pointer-events:none;
                overflow-y:auto;
                border-top:0;
                border-right:1px solid var(--line);
                box-shadow:18px 0 30px rgba(31,31,31,.12);
                transition:transform .28s ease, opacity .28s ease;
                z-index:80;
                will-change:transform,opacity;
            }
            .site-search-overlay{
                padding:84px 10px 16px;
            }
            .site-search-modal{
                padding:14px;
                gap:12px;
            }
            .site-search-row{
                grid-template-columns:1fr;
            }
            .site-search-submit{
                width:100%;
            }
            .mobile-nav-panel.open{
                transform:translateX(0);
                opacity:1;
                pointer-events:auto;
            }
            .mobile-nav-panel-head{
                width:100%;
                padding:14px 18px 12px;
            }
            .mobile-nav-links{
                width:100%;
                margin:0;
                padding:18px 18px 22px;
                gap:10px;
            }
            .mobile-nav-contact{
                width:100%;
                margin:0;
                padding:0 18px 18px;
            }
            .mobile-nav-links a{
                padding:14px 0;
                font-size:12px;
            }
            .site-footer{
                padding:44px 0 18px;
            }
            .site-footer-shell{
                width:min(100% - 20px,1600px);
                padding:22px 18px 18px;
            }
            .site-footer-grid{
                grid-template-columns:1fr;
                gap:20px;
            }
            .site-footer-payment-card{
                width:calc(50% - 5px);
                min-height:44px;
            }
            .site-footer-bottom{
                flex-direction:column;
                align-items:flex-start;
            }
            .site-footer-badges{
                justify-content:flex-start;
            }
            .page,
            .site-content,
            .home-layout,
            .main-content,
            .container,
            .shop-page-shell,
            .contact-shell{
                width:100% !important;
                max-width:none !important;
                margin-left:0 !important;
                margin-right:0 !important;
                padding-left:0 !important;
                padding-right:0 !important;
            }
            .site-header-inner,
            .mobile-nav-links,
            .site-footer-shell{
                width:100% !important;
                max-width:none !important;
                margin-left:0 !important;
                margin-right:0 !important;
            }
            .site-footer-shell{
                padding-left:12px !important;
                padding-right:12px !important;
            }
        }
        @media (max-width: 420px){
            .mobile-nav-panel{
                top:72px;
                height:calc(100vh - 72px);
            }
            .mobile-nav-panel-head{
                padding:14px 18px 12px;
            }
            .site-header-inner{
                width:100%;
                max-width:none;
                gap:8px;
                height:72px;
                padding-left:12px;
                padding-right:12px;
            }
            .site-brand{
                width:100%;
            }
            .site-brand img{
                max-height:68px;
                max-width:100%;
                width:100%;
                object-position:left center;
            }
            .site-action,
            .mobile-menu-toggle{
                width:38px;
                height:38px;
            }
            .site-action svg{
                width:18px;
                height:18px;
            }
            .mobile-menu-toggle .bars span{width:18px}
            .site-footer-shell{
                width:100%;
                max-width:none;
                padding:18px 12px 16px;
            }
            .site-footer-payment-card{
                width:100%;
            }
        }
    </style>
</head>
<body class="<?= $isHomeRoute ? 'is-home-page' : 'is-non-home-page' ?>">
<script>
    window.toggleMobileMenu = window.toggleMobileMenu || function (button) {
        const mobileMenuToggle = button || document.querySelector('[data-mobile-menu-toggle]');
        const mobileNavPanel = document.querySelector('[data-mobile-nav-panel]');
        const mobileNavState = document.getElementById('mobileNavToggleState');
        if (!mobileMenuToggle || !mobileNavPanel || !mobileNavState) {
            return;
        }

        const isOpen = !mobileNavState.checked;
        mobileNavState.checked = isOpen;
        mobileNavPanel.classList.toggle('open', isOpen);
        mobileNavPanel.style.display = isOpen ? 'block' : 'none';
        mobileMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };
</script>
<div class="page">
    <header class="site-header">
        <input type="checkbox" id="mobileNavToggleState" class="mobile-nav-toggle-state" aria-hidden="true">
        <div class="site-header-inner">
            <a class="site-brand" href="<?= htmlspecialchars($baseUrl) ?>" aria-label="Go to homepage">
                <img
                    src="<?= htmlspecialchars($siteLogoUrl) ?>"
                    alt="<?= htmlspecialchars($settings['shop_name'] ?? 'STYLE1') ?>"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high">
            </a>

            <nav class="site-nav" aria-label="Primary navigation">
                <?php foreach ($menuItems as $menuItem): ?>
                    <a
                        href="<?= htmlspecialchars($menuItem['url']) ?>"
                        class="<?= !empty($menuItem['active']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($menuItem['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="site-actions" aria-label="Header actions">
                <a class="site-action no-loader" href="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>" aria-label="Search products" data-header-search>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="6.5"></circle>
                        <path d="M16 16l4.5 4.5"></path>
                    </svg>
                </a>
                <a class="site-action" href="<?= htmlspecialchars($baseUrl . 'cart') ?>" aria-label="View cart" data-cart-link>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="9" cy="20" r="1.5"></circle>
                        <circle cx="18" cy="20" r="1.5"></circle>
                        <path d="M3 4h2l2.2 10.5a1 1 0 0 0 1 .8h9.7a1 1 0 0 0 1-.8L21 8H7.4"></path>
                    </svg>
                    <span class="site-action-badge" data-cart-count-badge style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= (int) $cartCount ?></span>
                </a>
                <label class="mobile-menu-toggle" for="mobileNavToggleState" role="button" aria-label="Open navigation menu" aria-expanded="false" data-mobile-menu-toggle>
                    <span class="bars" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </label>
            </div>
        </div>
        <div class="mobile-nav-panel" data-mobile-nav-panel>
            <div class="mobile-nav-panel-head">
                <div class="mobile-nav-panel-title">Menu</div>
                <button type="button" class="mobile-nav-close" aria-label="Close menu" onclick="toggleMobileMenu(document.querySelector('[data-mobile-menu-toggle]'))">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <nav class="mobile-nav-links" aria-label="Mobile navigation">
                <?php foreach ($menuItems as $menuItem): ?>
                    <a class="<?= !empty($menuItem['active']) ? 'active' : '' ?>" href="<?= htmlspecialchars($menuItem['url']) ?>"><?= htmlspecialchars($menuItem['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= htmlspecialchars($baseUrl . 'cart') ?>">Cart</a>
            </nav>
            <div class="mobile-nav-contact" aria-label="Contact details">
                <div class="mobile-nav-contact-head">Contact Details</div>
                <?php if (!empty($shopWhatsappDigits)): ?>
                    <a class="mobile-nav-contact-item whatsapp-btn" href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($whatsappLabel) ?></span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($mobileNavEmail)): ?>
                    <a class="mobile-nav-contact-item" href="mailto:<?= htmlspecialchars($mobileNavEmail) ?>">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($mobileNavEmail) ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="site-search-overlay" data-site-search-overlay aria-hidden="true">
        <div class="site-search-modal" role="dialog" aria-modal="true" aria-label="Search products">
            <div class="site-search-head">
                <span class="site-search-kicker">Product Search</span>
                <button class="site-search-close" type="button" aria-label="Close search" data-site-search-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form class="site-search-form no-loader" action="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>" method="get" data-site-search-form>
                <div class="site-search-row">
                    <input
                        class="site-search-input"
                        type="search"
                        name="search"
                        placeholder="Search products by name, SKU, or keyword"
                        autocomplete="off"
                        spellcheck="false"
                        data-site-search-input>
                    <button class="site-search-submit" type="submit">Search</button>
                </div>
                <div class="site-search-meta">Type at least 2 characters for live suggestions.</div>
                <div class="site-search-suggestions" data-site-search-suggestions></div>
            </form>
        </div>
    </div>
    <?php if ($promoEnabled && $promoImageUrl !== ''): ?>
        <div
            class="site-promo-popup"
            data-site-promo-popup
            data-promo-state-key="<?= htmlspecialchars($promoStateKey, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($promoLink !== ''): ?>
                <a
                    class="site-promo-popup-inner"
                    href="<?= htmlspecialchars($promoLink, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $promoOpenNewTab ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                    aria-label="Open special promo">
                    <img class="site-promo-popup-image" src="<?= htmlspecialchars($promoImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Special promo">
                </a>
            <?php else: ?>
                <div class="site-promo-popup-inner" aria-label="Special promo">
                    <img class="site-promo-popup-image" src="<?= htmlspecialchars($promoImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Special promo">
                </div>
            <?php endif; ?>
            <button type="button" class="site-promo-popup-close" data-site-promo-close aria-label="Close promo popup">&times;</button>
        </div>
    <?php endif; ?>
    <div class="site-content">
<?php
    }

    function customer_layout_end(array $options = []): void
    {
        global $settings;

        ?>
    </div>
    <?php require_once ROOT_PATH . 'views/layouts/customer_footer.php'; customer_footer_render(is_array($settings) ? $settings : [], defined('BASE_URL') ? BASE_URL : '/'); ?>
    <div id="globalLoader" class="global-loader-overlay">
        <img src="<?= htmlspecialchars((defined('BASE_URL') ? BASE_URL : '/') . 'assets/preloader.gif', ENT_QUOTES, 'UTF-8') ?>" alt="Loading" class="global-loader-gif">
        <div class="global-loader-text">Loading...</div>
    </div>
    <script>
        const baseUrl = <?= json_encode(defined('BASE_URL') ? BASE_URL : '/') ?>;
        const csrfToken = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '') ?>;
        const cartCountStorageKey = 'style1_cart_count';

        window.updateCartUi = function (count) {
            const qty = Math.max(0, parseInt(count, 10) || 0);
            try {
                localStorage.setItem(cartCountStorageKey, String(qty));
            } catch (error) {}
            document.querySelectorAll('[data-cart-count-badge]').forEach(function (badge) {
                badge.textContent = String(qty);
                badge.style.display = qty > 0 ? '' : 'none';
            });
            document.querySelectorAll('[data-floating-cart]').forEach(function (widget) {
                widget.style.display = qty > 0 ? 'grid' : 'none';
            });
            document.querySelectorAll('[data-mobile-cart-badge]').forEach(function (badge) {
                badge.textContent = String(qty);
                badge.style.display = qty > 0 ? '' : 'none';
            });
        };

        window.addEventListener('cart:changed', function (event) {
            if (!event || !event.detail) {
                return;
            }

            window.updateCartUi(event.detail.count);
        });

        window.refreshCartUi = function () {
            return fetch(baseUrl + 'cart/count', {
                headers: { 'Accept': 'application/json' }
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.success) {
                return;
            }

            window.updateCartUi(data.count || 0);
        }).catch(function () {});
        };

        window.addEventListener('DOMContentLoaded', function () {
            const searchLinks = document.querySelectorAll('[data-header-search]');
            const searchOverlay = document.querySelector('[data-site-search-overlay]');
            const searchForm = document.querySelector('[data-site-search-form]');
            const searchInput = document.querySelector('[data-site-search-input]');
            const searchCloseButton = document.querySelector('[data-site-search-close]');
            const suggestionsBox = document.querySelector('[data-site-search-suggestions]');

            if (!searchLinks.length || !searchOverlay || !searchForm || !searchInput || !searchCloseButton || !suggestionsBox) {
                return;
            }

            let activeSuggestionIndex = -1;
            let currentSuggestions = [];
            let pendingFetchController = null;
            let searchDebounceTimer = null;

            const escapeHtml = function (value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            const closeSearch = function () {
                searchOverlay.classList.remove('open');
                searchOverlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                activeSuggestionIndex = -1;
                currentSuggestions = [];
                suggestionsBox.innerHTML = '';
                suggestionsBox.classList.remove('open');
            };

            const openSearch = function () {
                searchOverlay.classList.add('open');
                searchOverlay.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                window.setTimeout(function () {
                    searchInput.focus();
                    searchInput.select();
                }, 0);
            };

            const renderSuggestions = function (items) {
                currentSuggestions = Array.isArray(items) ? items : [];
                activeSuggestionIndex = -1;
                if (!currentSuggestions.length) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.classList.remove('open');
                    return;
                }

                suggestionsBox.innerHTML = currentSuggestions.map(function (item, index) {
                    const title = escapeHtml(item.title || 'Product');
                    const category = escapeHtml(item.category_name || 'Shop');
                    const thumb = escapeHtml(item.thumbnail_url || '');
                    const price = escapeHtml(item.price_label || '');
                    return '' +
                        '<button type="button" class="site-search-suggestion" data-search-suggestion-index="' + index + '" data-search-suggestion-url="' + escapeHtml(item.url || (baseUrl + 'shop/categories')) + '">' +
                            '<img class="site-search-suggestion-thumb" src="' + thumb + '" alt="' + title + '">' +
                            '<span>' +
                                '<span class="site-search-suggestion-title">' + title + '</span>' +
                                '<span class="site-search-suggestion-meta">' + category + (price ? ' · ' + price : '') + '</span>' +
                            '</span>' +
                        '</button>';
                }).join('');
                suggestionsBox.classList.add('open');
            };

            const applyActiveSuggestion = function () {
                const nodes = suggestionsBox.querySelectorAll('[data-search-suggestion-index]');
                nodes.forEach(function (node, idx) {
                    if (idx === activeSuggestionIndex) {
                        node.classList.add('active');
                        node.scrollIntoView({ block: 'nearest' });
                    } else {
                        node.classList.remove('active');
                    }
                });
            };

            const fetchSuggestions = function (term) {
                if (pendingFetchController) {
                    pendingFetchController.abort();
                }
                pendingFetchController = new AbortController();

                const url = baseUrl + 'shop/searchSuggestions?term=' + encodeURIComponent(term);
                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: pendingFetchController.signal
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (!data || !data.success) {
                        renderSuggestions([]);
                        return;
                    }
                    renderSuggestions(data.items || []);
                }).catch(function () {
                    renderSuggestions([]);
                });
            };

            searchLinks.forEach(function (searchLink) {
                searchLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openSearch();
                });
            });

            searchCloseButton.addEventListener('click', closeSearch);
            searchOverlay.addEventListener('click', function (event) {
                if (event.target === searchOverlay) {
                    closeSearch();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && searchOverlay.classList.contains('open')) {
                    closeSearch();
                }
            });

            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim();
                if (searchDebounceTimer) {
                    clearTimeout(searchDebounceTimer);
                }

                if (term.length < 2) {
                    renderSuggestions([]);
                    return;
                }

                searchDebounceTimer = window.setTimeout(function () {
                    fetchSuggestions(term);
                }, 180);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown') {
                    if (!currentSuggestions.length) {
                        return;
                    }
                    event.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % currentSuggestions.length;
                    applyActiveSuggestion();
                    return;
                }

                if (event.key === 'ArrowUp') {
                    if (!currentSuggestions.length) {
                        return;
                    }
                    event.preventDefault();
                    activeSuggestionIndex = activeSuggestionIndex <= 0
                        ? (currentSuggestions.length - 1)
                        : (activeSuggestionIndex - 1);
                    applyActiveSuggestion();
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (!currentSuggestions.length) {
                        return;
                    }
                    const targetIndex = activeSuggestionIndex >= 0 ? activeSuggestionIndex : 0;
                    const targetSuggestion = currentSuggestions[targetIndex];
                    if (targetSuggestion && targetSuggestion.url) {
                        window.location.href = targetSuggestion.url;
                    }
                }
            });

            suggestionsBox.addEventListener('click', function (event) {
                const target = event.target.closest('[data-search-suggestion-url]');
                if (!target) {
                    return;
                }
                const suggestionUrl = target.getAttribute('data-search-suggestion-url') || '';
                if (suggestionUrl !== '') {
                    window.location.href = suggestionUrl;
                }
            });

            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const term = searchInput.value.trim();
                searchInput.value = term;
                return false;
            });
        });

        window.addToCart = function (productId, productTitle, productPrice, productImg, quantity, variants, variantKey) {
            const qty = Math.max(1, parseInt(quantity, 10) || 1);
            return fetch(baseUrl + 'cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({
                    _csrf: csrfToken,
                    id: productId,
                    title: productTitle,
                    price: productPrice,
                    img: productImg,
                    quantity: qty,
                    variants: variants || '',
                    variant_key: variantKey || ''
                })
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Failed to add to cart');
                }

                window.updateCartUi(data.count || 0);
                window.dispatchEvent(new CustomEvent('cart:changed', { detail: { count: data.count || 0 } }));
                if (typeof window.refreshCartUi === 'function') {
                    window.refreshCartUi();
                }
                return data;
            });
        };

        window.toggleMobileMenu = function (button) {
            const mobileMenuToggle = button || document.querySelector('[data-mobile-menu-toggle]');
            const mobileNavPanel = document.querySelector('[data-mobile-nav-panel]');
            const mobileNavState = document.getElementById('mobileNavToggleState');
            if (!mobileMenuToggle || !mobileNavPanel || !mobileNavState) {
                return;
            }

            const isOpen = !mobileNavState.checked;
            mobileNavState.checked = isOpen;
            mobileNavPanel.classList.toggle('open', isOpen);
            mobileMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        document.addEventListener('DOMContentLoaded', function () {
            const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
            const mobileNavPanel = document.querySelector('[data-mobile-nav-panel]');
            const mobileNavState = document.getElementById('mobileNavToggleState');
            try {
                const storedCount = parseInt(localStorage.getItem(cartCountStorageKey) || '0', 10) || 0;
                window.updateCartUi(storedCount);
            } catch (error) {}
            if (typeof window.refreshCartUi === 'function') {
                window.refreshCartUi();
            }

            if (mobileMenuToggle && mobileNavPanel && mobileNavState) {
                const closeMenu = function () {
                    mobileNavPanel.classList.remove('open');
                    mobileNavState.checked = false;
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                };

                mobileNavPanel.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', closeMenu);
                });

                document.addEventListener('click', function (event) {
                    if (!mobileNavPanel.classList.contains('open')) {
                        return;
                    }

                    if (mobileNavPanel.contains(event.target) || mobileMenuToggle.contains(event.target)) {
                        return;
                    }

                    closeMenu();
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeMenu();
                    }
                });
            }
        });

        let globalLoaderTimeout;
        function showGlobalLoader() {
            clearTimeout(globalLoaderTimeout);
            const loader = document.getElementById('globalLoader');
            if (loader) loader.style.display = 'flex';
        }
        function hideGlobalLoader() {
            clearTimeout(globalLoaderTimeout);
            const loader = document.getElementById('globalLoader');
            if (loader) loader.style.display = 'none';
        }
        document.addEventListener('click', function(event) {
            const link = event.target.closest('a');
            if (!link || link.classList.contains('no-loader') || link.target === '_blank' || link.hasAttribute('download')) return;
            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (link.hostname !== window.location.hostname) return;
            showGlobalLoader();
        }, false);
        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (!form || form.classList.contains('no-loader')) return;
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                hideGlobalLoader();
                return;
            }
            showGlobalLoader();
        }, false);
        window.addEventListener('pageshow', function() { hideGlobalLoader(); });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const promoPopup = document.querySelector('[data-site-promo-popup]');
            if (!promoPopup) {
                return;
            }

            const promoClose = promoPopup.querySelector('[data-site-promo-close]');
            const promoStateKey = promoPopup.getAttribute('data-promo-state-key') || '';
            const storageKey = 'style1_promo_closed_' + promoStateKey;

            try {
                if (promoStateKey && sessionStorage.getItem(storageKey) === '1') {
                    promoPopup.hidden = true;
                    return;
                }
            } catch (error) {}

            if (promoClose) {
                promoClose.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    promoPopup.hidden = true;
                    try {
                        if (promoStateKey) {
                            sessionStorage.setItem(storageKey, '1');
                        }
                    } catch (error) {}
                });
            }
        });
    </script>
</div>
</body>
</html>
<?php
    }
}
