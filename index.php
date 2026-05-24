<?php
$incomingRequest = isset($_GET['url']) ? trim((string) $_GET['url'], '/') : '';
if ($incomingRequest !== '' && !in_array($incomingRequest, ['home', 'home2'], true)) {
    require_once __DIR__ . '/app.php';
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$appRoot = defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/';
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/config/config.php';
}
require_once $appRoot . 'config/db.php';
require_once $appRoot . 'models/Product.php';
require_once $appRoot . 'models/Category.php';
require_once $appRoot . 'models/Setting.php';
require_once $appRoot . 'helpers/ImageHelper.php';
require_once $appRoot . 'helpers/SeoHelper.php';
require_once $appRoot . 'helpers/KokoPricingHelper.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$productModel = new Product();
$categoryModel = new Category();
$settingModel = new Setting();

$settings = $settingModel->getAllPairs();
$homeTitle = SeoHelper::homeTitle($settings);
$title = SeoHelper::shopName($settings);
$seo = SeoHelper::defaultSeo($settings, [
    'seo_title' => $homeTitle,
    'seo_description' => SeoHelper::trimText(($settings['shop_about'] ?? '') . ' Shop featured products, latest arrivals, categories, and offers.', 160),
    'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL),
    'seo_json_ld' => [
        SeoHelper::buildOrganizationSchema($settings),
        SeoHelper::buildWebsiteSchema($settings),
        SeoHelper::buildBreadcrumbSchema([
            ['name' => $settings['shop_name'] ?? 'Home', 'url' => SeoHelper::absoluteUrl(BASE_URL)]
        ]),
        SeoHelper::buildWebPageSchema('CollectionPage', $homeTitle, SeoHelper::absoluteUrl(BASE_URL), SeoHelper::trimText(($settings['shop_about'] ?? ''), 160))
    ]
]);
$seo_title = $seo['seo_title'];
$seo_description = $seo['seo_description'];
$seo_canonical = $seo['seo_canonical'];
$seo_image = $seo['seo_image'];
$seo_type = $seo['seo_type'];
$seo_robots = $seo['seo_robots'];
$seo_json_ld = $seo['seo_json_ld'];
$categories = $categoryModel->getAll();
$featuredProducts = $productModel->getFeatured(20);
$latestProducts = $productModel->getLatest(24);
$saleProducts = $productModel->getAllOnSale();

$mainCategories = array_values(array_filter($categories, function ($cat) {
    return empty($cat['parent_id']);
}));
$categoryTiles = array_slice($mainCategories, 0, 7);

$heroSlides = [];
for ($i = 1; $i <= 3; $i++) {
    $imageKey = 'hero_slide_' . $i . '_image';
    $mobileImageKey = 'hero_slide_' . $i . '_mobile_image';
    $linkKey = 'hero_slide_' . $i . '_link';
    $slideImage = trim((string) ($settings[$imageKey] ?? ''));

    if ($slideImage === '') {
        continue;
    }

    $slideUrl = ImageHelper::settingsImageUrl($slideImage, '');
    if ($slideUrl === '') {
        continue;
    }

    $heroSlides[] = [
        'image_url' => $slideUrl,
        'image_name' => basename((string) parse_url($slideUrl, PHP_URL_PATH)),
        'mobile_image_url' => ImageHelper::settingsImageUrl((string) ($settings[$mobileImageKey] ?? ''), ''),
        'mobile_image_name' => basename((string) parse_url(ImageHelper::settingsImageUrl((string) ($settings[$mobileImageKey] ?? ''), ''), PHP_URL_PATH)),
        'link' => trim((string) ($settings[$linkKey] ?? '')),
        'title' => 'Style1 Hero Slide ' . $i
    ];
}

$heroProduct = $featuredProducts[0] ?? ($latestProducts[0] ?? null);
$heroImage = !empty($heroProduct['main_image'])
    ? ImageHelper::uploadUrl($heroProduct['main_image'], '')
    : '';
if ($heroImage === '' && empty($heroSlides)) {
    $heroImage = 'https://lh3.googleusercontent.com/aida-public/AB6AXuB_aSbEcuGGW3_h1Tuxr82RPyIri1f54bCKMBx3lk4ub6h9Hzxl1_i8cf_OAKE2UpwM4Sc7j7wpLvFcAPSM9uC9vHoLQX2V45KDYpZebrxBuwESJxLuHwXEDP-00kVQ1LiW3Kopio0H7wSwcG_FTWmlBaq12F-DyhL0ygx4hHIDHd7fefam0DtCFwf5l4cdQp6BgUoUgpqbPwaY_CQMvYmYHulcqkKb0tP5DBtOUn8xFwwpDWVjFUGZK6hx_c9ZJHAeUTmh8x7qe04';
}
$heroTitle = $heroProduct['title'] ?? 'Modern Essentials';
$heroSubtitle = !empty($heroProduct['category_name'])
    ? $heroProduct['category_name']
    : 'The 2024 Collection';
$heroSlidesCount = !empty($heroSlides) ? count($heroSlides) : 1;

function renderHomeKokoTeaser(array $product, array $settings, string $context = 'default'): string
{
    static $kokoLogoUrl = null;
    if ($kokoLogoUrl === null) {
        $kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
    }

    if (!KokoPricingHelper::isEnabled($settings)) {
        return '';
    }

    $basePrice = KokoPricingHelper::getEffectiveProductPrice($product);
    if ($basePrice <= 0) {
        return '';
    }

    $teaser = KokoPricingHelper::getInstallmentData($basePrice, $settings);
    $currency = htmlspecialchars($settings['currency_symbol'] ?? 'LKR');

    return '<div class="koko-installment-teaser" aria-label="KOKO installment plan">'
        . '<span class="koko-installment-text">or 3 x ' . $currency . ' ' . number_format((float) $teaser['installment_amount'], 0) . '</span>'
        . '<img src="' . htmlspecialchars($kokoLogoUrl) . '" alt="KOKO" class="koko-installment-logo" style="height:16px;width:auto;flex-shrink:0;display:block;">'
        . '</div>';
}
?>
<?php require_once 'views/layouts/customer_layout.php'; customer_layout_start(); ?>
<style>
        :root{
            --primary:var(--accent, #b68a2d);--primary-strong:var(--accent-red, #d4af37);--surface:#fcf9f8;--surface-low:#f6f3f2;--surface-mid:#f0eded;--surface-high:#eae7e7;--surface-highest:#e5e2e1;--ink:#1c1b1b;--muted:#6d6665;--shadow:0 24px 60px rgba(28,27,27,.08);--shadow-soft:0 14px 30px rgba(28,27,27,.06)
        }
        *{box-sizing:border-box} html{scroll-behavior:smooth}
        body{margin:0;font-family:"Manrope",sans-serif;background:var(--surface);color:var(--ink)}
        h1,h2,h3,h4,h5{font-family:"Noto Serif",serif;font-weight:400;margin:0}
        a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
        .page{overflow:hidden}
        .container{
            width:min(1600px,calc(100% - 96px));
            margin:0 auto;
            padding-left:24px;
            box-sizing:border-box;
        }
        .main{padding-top:0}
        .hero{position:relative;width:100%;height:auto !important;min-height:0 !important;max-height:none !important;background:transparent;overflow:visible !important}
        .hero-media{position:relative;inset:auto;width:100%;height:auto !important;min-height:0 !important;max-height:none !important;background:transparent;overflow:visible !important}
        .hero-slider{position:relative;inset:auto;display:flex;align-items:flex-start;overflow-x:auto;overflow-y:visible;scroll-snap-type:x mandatory;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none;border-radius:0 !important}
        .hero-slider::-webkit-scrollbar{display:none}
        .hero-slide{flex:0 0 100% !important;min-width:100% !important;position:relative;display:flex;align-items:flex-start;justify-content:flex-start;scroll-snap-align:start;background:transparent !important;padding:0;aspect-ratio:auto !important;overflow:visible !important;border-radius:0 !important;box-shadow:none !important}
        .hero-slide-link,.hero-slide-frame{display:block;width:100%;height:auto !important;min-height:0 !important}
        .hero-slide picture{display:block;width:100%;height:auto !important;min-height:0 !important;overflow:visible !important;border-radius:0 !important}
        .hero-slide img{display:block;width:100% !important;height:auto !important;max-width:100%;max-height:none !important;object-fit:contain !important;object-position:center center;border-radius:0 !important}
        .hero-media > img{display:block;width:100% !important;height:auto !important;max-width:100%;max-height:none !important;object-fit:contain !important;object-position:center center;border-radius:0 !important}
        .hero-slider-dots{position:absolute;left:0;right:0;bottom:18px;z-index:3;display:flex;justify-content:center;gap:8px;padding:0 16px;pointer-events:none}
        .hero-slider-dot{width:18px;height:3px;border:0;background:#d4af37;padding:0;pointer-events:auto;cursor:pointer}
        .hero-slider-dot.active{background:#b68a2d}
        .hero-overlay{display:none}
        .section{padding:88px 0}.section-surface-low{background:var(--surface-low)}.section-dark{background:#1a1a1a;color:#fff;padding:118px 0 132px}
        .section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:28px}
        .section-head-left{max-width:720px}
        .label{display:block;margin-bottom:6px;font-size:11px;letter-spacing:.26em;text-transform:uppercase;color:rgba(28,27,27,.55)}
        .section-dark .label{color:rgba(255,255,255,.55)}
        .section-title{font-size:clamp(34px,4vw,54px);letter-spacing:-.04em;line-height:1.02}
        .section-dark .section-title{color:#fff}
        .section-copy{margin-top:10px;color:var(--muted);line-height:1.8;max-width:62ch;font-size:15px}
        .section-dark .section-copy{color:rgba(255,255,255,.68)}
        .section-link{font-size:11px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:inherit;display:inline-flex;align-items:center;gap:10px}
        .section-link::after{content:"";width:44px;height:1px;background:currentColor;opacity:.35}
        .category-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}
        .category-card{position:relative;display:block;aspect-ratio:4/5;overflow:hidden;background:var(--surface-highest);box-shadow:var(--shadow-soft)}
        .category-card picture,.category-card img{display:block;width:100%;height:100%}
        .category-card img{object-fit:cover;transition:transform .7s ease}
        .category-card:hover img{transform:scale(1.04)}
        .carousel-actions{display:flex;gap:14px;margin-left:auto}
        .icon-btn{width:46px;height:46px;border:0;border-radius:0;background:transparent;color:inherit;box-shadow:inset 0 0 0 1px rgba(28,27,27,.12);cursor:pointer}
        .section-dark .icon-btn{box-shadow:inset 0 0 0 1px rgba(255,255,255,.14)}
        .hide-scrollbar{-ms-overflow-style:none;scrollbar-width:none}.hide-scrollbar::-webkit-scrollbar{display:none}
        .product-rail,.sale-rail{display:flex;gap:26px;overflow-x:auto;padding-bottom:8px}.product-rail{scroll-snap-type:x mandatory}.sale-rail{align-items:flex-start;padding-bottom:18px}
        .product-card{
            min-width:320px;
            max-width:320px;
            scroll-snap-align:start;
            background:var(--surface);
            box-shadow:none;
            border:0;
            outline:0;
            margin:0;
        }
        .product-card-link{
            display:block;
            color:inherit;
            text-decoration:none;
        }
        .product-media{position:relative;aspect-ratio:3/4;overflow:hidden;background:var(--surface-high)}
        .product-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease}.product-card:hover .product-media img{transform:scale(1.05)}
        .product-body{padding:22px 18px 20px}
        .product-name,
        .arrival-item h4,
        .sale-card h4{
            display:block;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
            text-overflow:ellipsis;
            min-height:2.5em;
        }
        .product-name{font-size:18px;line-height:1.22}
        .product-price-row{display:flex;align-items:baseline;gap:10px;margin-top:8px;margin-bottom:8px;flex-wrap:wrap}
        .product-price{font-size:16px;font-weight:800;white-space:nowrap;color:var(--primary)}
        .product-old-price{font-size:13px;white-space:nowrap;color:rgba(28,27,27,.45);text-decoration:line-through}
        .product-badge{
            position:absolute;
            top:14px;
            z-index:2;
            display:inline-flex;
            align-items:center;
            padding:8px 12px;
            font-size:10px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:#fff;
            background:var(--primary);
        }
        .product-badge.discount{right:14px}
        .product-badge.shipping{
            left:14px;
            bottom:14px;
            top:auto;
            background:var(--ink);
            border-radius:0;
        }
        .product-desc{color:var(--muted);font-size:13px;line-height:1.65;margin:0 0 18px}
        .product-btn{width:100%;min-height:50px;border:0;border-radius:0;background:transparent;color:var(--ink);font-size:11px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;box-shadow:inset 0 0 0 1px rgba(28,27,27,.12)}
        .product-card .koko-installment-teaser{display:flex;align-items:center;flex-direction:row;margin-top:8px;gap:6px;flex-wrap:nowrap;white-space:nowrap;overflow:hidden;min-width:0}
        .product-card .koko-installment-text{min-width:0;overflow:hidden;text-overflow:ellipsis}
        .product-card .koko-installment-logo{height:16px;width:auto;flex-shrink:0;display:block}
</style>
<style>
        .grid-arrivals{display:grid;grid-template-columns:repeat(4,1fr);gap:28px 26px}
        .arrival-item{display:grid;gap:12px}
        .arrival-image{position:relative;aspect-ratio:4/5;overflow:hidden;background:var(--surface-high);box-shadow:var(--shadow-soft)}
        .arrival-image img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease}
        .arrival-item:hover .arrival-image img{transform:scale(1.05)}
        .chip{font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:rgba(28,27,27,.48)}
        .arrival-item h4{font-size:18px;line-height:1.2}
        .arrival-price-row{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
        .arrival-price{font-weight:800;font-size:16px;color:var(--primary)}
        .arrival-old-price{font-size:13px;white-space:nowrap;color:rgba(28,27,27,.45);text-decoration:line-through}
        .arrival-item.is-hidden{display:none !important}
        .load-more-wrap{padding-top:26px;display:flex;justify-content:center}
        .load-more-btn{min-height:50px;padding:0 28px;border:0;background:var(--ink);color:#fff;font-size:10px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;cursor:pointer}
        .load-more-btn[hidden]{display:none !important}
        .sale-card{min-width:320px;max-width:320px;display:grid;gap:14px}
        .sale-media{position:relative;aspect-ratio:4/5;overflow:hidden;background:#2a2a2a;margin-bottom:0}
        .sale-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease,opacity .3s ease}
        .sale-card:hover .sale-media img{transform:scale(1.05);opacity:.88}
        .badge{position:absolute;top:0;right:0;background:var(--primary);color:#fff;padding:8px 12px;font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .sale-card h4{font-size:19px;line-height:1.2;margin-bottom:8px}
        .price-row{display:flex;gap:12px;align-items:center}
        .price-row .sale-price{color:var(--primary);font-weight:800}
        .price-row .old-price{color:rgba(255,255,255,.34);text-decoration:line-through;font-size:13px}
        .arrival-badge{
            position:absolute;
            top:14px;
            z-index:2;
            display:inline-flex;
            align-items:center;
            padding:8px 12px;
            font-size:10px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:#fff;
            background:var(--primary);
        }
        .arrival-badge.discount{right:14px}
        .arrival-badge.shipping{
            left:14px;
            bottom:14px;
            top:auto;
            background:var(--ink);
            border-radius:0;
        }
        .spacer{height:6px}
        .featured-products-section .section-title,
        .recently-added-section .section-title{
            font-family:"Manrope",sans-serif !important;
            text-transform:uppercase;
        }
        .featured-products-section .product-price{
            color:#d4af37 !important;
        }
        .featured-products-section .product-badge.discount{
            background:#d4af37 !important;
        }
        .recently-added-section .arrival-item h4{
            font-family:"Manrope",sans-serif !important;
        }
        .recently-added-section .arrival-price{
            color:#d4af37 !important;
        }
        .recently-added-section .arrival-badge.discount{
            background:#d4af37 !important;
        }
        .sale-archive-section{
            background:#f0eded url('assets/offer-background.jpg') center/cover no-repeat !important;
            color:var(--ink);
            padding-top:140px;
            padding-bottom:160px;
        }
        .sale-archive-section .container{
            padding-left:40px;
            padding-right:40px;
        }
        .sale-archive-section .section-title{
            font-family:"Manrope",sans-serif !important;
            text-transform:uppercase;
        }
        .sale-archive-section .label{
            color:rgba(28,27,27,.55);
        }
        .sale-archive-section .section-copy{
            color:var(--muted);
            margin-top:10px;
        }
        .sale-archive-section .sale-card h4{
            color:var(--ink);
            font-family:"Manrope",sans-serif !important;
        }
        .sale-archive-section .price-row .sale-price{
            color:#d4af37 !important;
        }
        .sale-archive-section .badge{
            background:#d4af37 !important;
        }
        .sale-archive-section .price-row .old-price{
            color:rgba(28,27,27,.45);
        }
        .sale-archive-section .carousel-actions .icon-btn{
            color:var(--ink);
            box-shadow:inset 0 0 0 1px rgba(28,27,27,.12);
        }
        @media (max-width:1180px){
            .container{width:min(100% - 48px,1600px);padding-left:20px}
            .hero{min-height:0}
            .category-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
            .grid-arrivals{grid-template-columns:repeat(2,minmax(0,1fr))}
            .section-dark{padding:104px 0 116px}
        }
        @media (max-width:760px){
            .page{
                overflow-x:hidden;
                overflow-y:visible;
            }
            .container{
                width:100%;
                max-width:100%;
                padding-left:0;
                padding-right:0;
            }
            .hero{
                width:100% !important;
                margin-left:0 !important;
                height:auto;
                min-height:0;
                max-height:none;
                background:#fff;
                overflow:visible !important;
            }
            .hero-media{position:relative;inset:auto;height:auto;min-height:0;background:#fff;overflow:visible !important}
            .hero-slider{
                position:relative;
                inset:auto;
                height:auto;
                min-height:0;
                display:flex;
                align-items:flex-start;
                overflow-x:auto;
                overflow-y:visible;
                scroll-snap-type:x mandatory;
                scroll-behavior:smooth;
            }
            .hero-slide{
                flex:0 0 100%;
                min-width:100%;
                min-height:0;
                height:auto;
                aspect-ratio:auto !important;
                padding:0;
                align-items:flex-start;
                justify-content:flex-start;
                background:#fff;
                display:flex;
                scroll-snap-align:start;
            }
            .hero-slide picture,.hero-slide-link,.hero-slide-frame{
                width:100%;
                height:auto !important;
                min-height:0;
                display:block;
                overflow:visible !important;
            }
            .hero-slide img,.hero-media > img{
                width:100% !important;
                height:auto !important;
                max-width:100%;
                max-height:none;
                display:block;
                padding:0;
                object-fit:unset !important;
                object-position:center;
                background:#fff;
            }
            .hero-slider-dots{bottom:12px;gap:6px}
            .hero-slider-dot{width:16px;height:3px}
            .section{padding:68px 0}
            .section-head{flex-direction:column;align-items:flex-start}
            .category-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
            .grid-arrivals{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px}
            .product-card{min-width:280px;max-width:280px;box-shadow:none;border:0;outline:0;margin:0}
            .featured-products-section .section-head-left{
                padding-left:12px;
            }
            .featured-products-section .product-rail{
                padding-left:12px;
                padding-right:12px;
            }
            .recently-added-section .section-head-left{
                padding-left:12px;
            }
            .recently-added-section .grid-arrivals{
                padding-left:12px;
                padding-right:12px;
            }
            .sale-archive-section .section-title{
                color:var(--ink) !important;
            }
            .sale-archive-section .carousel-actions{
                display:none;
            }
            .sale-archive-section{
                padding-top:120px !important;
                padding-bottom:130px !important;
            }
            .sale-archive-section .container{
                padding-left:20px !important;
                padding-right:20px !important;
            }
            .section-dark{padding:58px 0 64px}
            .sale-card{min-width:72vw;max-width:72vw}
            .sale-media{aspect-ratio:5/6}
            .section-dark .section-head-left{
                padding-left:16px !important;
            }
            .section-dark .section-link{
                padding-left:16px !important;
                padding-right:16px !important;
            }
            .section-dark .sale-rail{
                padding-left:16px !important;
                padding-right:16px !important;
            }
        }
        @media (max-width:1023px){
            html, body{
                padding-left:0 !important;
                padding-right:0 !important;
            }
        }
    </style>

    <main class="main">
        <section class="hero">
            <div class="hero-media">
                <?php if (!empty($heroSlides)): ?>
                    <div class="hero-slider" data-hero-slider>
                        <?php foreach ($heroSlides as $index => $slide): ?>
                            <?php
                            $desktopSlideUrl = $slide['image_url'] ?? '';
                            $desktopSlideName = $slide['image_name'] ?? '';
                            $mobileSlideUrl = $slide['mobile_image_url'] ?? '';
                            $mobileSlideName = $slide['mobile_image_name'] ?? '';
                            ?>
                            <div class="hero-slide">
                                <?php if (!empty($slide['link'])): ?>
                                    <a class="hero-slide-link" href="<?= htmlspecialchars($slide['link']) ?>" aria-label="<?= htmlspecialchars($slide['title']) ?>">
                                        <picture style="display:block;width:100%;height:auto;">
                                            <?php if (!empty($mobileSlideUrl)): ?>
                                                <source media="(max-width: 760px)" srcset="<?= htmlspecialchars($mobileSlideUrl) ?>">
                                            <?php endif; ?>
                                            <img
                                                src="<?= htmlspecialchars($desktopSlideUrl) ?>"
                                                alt="<?= htmlspecialchars($slide['title'] ?? 'Hero slide') ?>"
                                                loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                                decoding="<?= $index === 0 ? 'sync' : 'async' ?>"
                                                fetchpriority="<?= $index === 0 ? 'high' : 'low' ?>"
                                                style="display:block;width:100% !important;height:auto !important;max-width:100%;max-height:none !important;object-fit:contain !important;object-position:center center;border-radius:0 !important;">
                                        </picture>
                                    </a>
                                <?php else: ?>
                                    <div class="hero-slide-frame">
                                        <picture style="display:block;width:100%;height:auto;">
                                            <?php if (!empty($mobileSlideUrl)): ?>
                                                <source media="(max-width: 760px)" srcset="<?= htmlspecialchars($mobileSlideUrl) ?>">
                                            <?php endif; ?>
                                            <img
                                                src="<?= htmlspecialchars($desktopSlideUrl) ?>"
                                                alt="<?= htmlspecialchars($slide['title'] ?? 'Hero slide') ?>"
                                                loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                                decoding="<?= $index === 0 ? 'sync' : 'async' ?>"
                                                fetchpriority="<?= $index === 0 ? 'high' : 'low' ?>"
                                                style="display:block;width:100% !important;height:auto !important;max-width:100%;max-height:none !important;object-fit:contain !important;object-position:center center;border-radius:0 !important;">
                                        </picture>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($heroSlides) > 1): ?>
                        <div class="hero-slider-dots" data-hero-dots>
                            <?php foreach ($heroSlides as $index => $slide): ?>
                                <button
                                    type="button"
                                    class="hero-slider-dot <?= $index === 0 ? 'active' : '' ?>"
                                    aria-label="Go to slide <?= $index + 1 ?>"
                                    data-slide-to="<?= $index ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($heroTitle) ?>">
                <?php endif; ?>
            </div>
        </section>

        <section class="section section-surface-low featured-products-section">
            <div class="container">
                <div class="section-head">
                    <div class="section-head-left">
                        <span class="label">Curated Selection</span>
                        <h2 class="section-title">Trending Products</h2>
                    </div>
                    <div class="carousel-actions">
                        <button class="icon-btn" type="button" aria-label="Previous featured products" data-product-prev>←</button>
                        <button class="icon-btn" type="button" aria-label="Next featured products" data-product-next>→</button>
                    </div>
                </div>

                <div class="product-rail hide-scrollbar" data-product-rail>
                    <?php if (!empty($featuredProducts)): ?>
                        <?php foreach ($featuredProducts as $product): ?>
                            <?php
                            $productImage = ImageHelper::uploadUrl(
                                $product['main_image'] ?? '',
                                'https://via.placeholder.com/720x960?text=' . urlencode($product['title'] ?? 'Product')
                            );
                            $regularPrice = (float) ($product['price'] ?? 0);
                            $salePrice = (float) ($product['sale_price'] ?? 0);
                            $hasDiscount = $regularPrice > 0 && $salePrice > 0 && $salePrice < $regularPrice;
                            $discountPercent = $hasDiscount
                                ? (int) round((($regularPrice - $salePrice) / $regularPrice) * 100)
                                : 0;
                            $displayPrice = $hasDiscount ? $salePrice : $regularPrice;
                            $productShortDescription = trim((string) ($product['short_description'] ?? ''));
                            $productSummary = $productShortDescription !== ''
                                ? $productShortDescription
                                : (string) ($product['description'] ?? '');
                            ?>
                            <article class="product-card">
                                <a class="product-card-link" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
                                    <div class="product-media">
                                        <?= ImageHelper::renderResponsivePicture(
                                            $product['main_image'] ?? '',
                                            $productImage,
                                            [
                                                'alt' => $product['title'] ?? 'Product',
                                                'loading' => 'lazy',
                                                'decoding' => 'async',
                                                'fetchpriority' => 'low'
                                            ],
                                            'product_card'
                                        ) ?>
                                        <?php if ($hasDiscount): ?>
                                            <span class="product-badge discount">-<?= $discountPercent ?>%</span>
                                        <?php endif; ?>
                                        <?php if (!empty($product['free_shipping'])): ?>
                                            <span class="product-badge shipping">Free Shipping</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-body">
                                        <div class="product-name"><?= htmlspecialchars($product['title'] ?? 'Product') ?></div>
                                        <div class="product-price-row">
                                            <div class="product-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($displayPrice, 0) ?></div>
                                            <?php if ($hasDiscount): ?>
                                                <div class="product-old-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($regularPrice, 0) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?= renderHomeKokoTeaser($product, $settings, 'featured') ?>
                                        <p class="product-desc"><?= htmlspecialchars(SeoHelper::trimText($productSummary, 90)) ?></p>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="product-card" style="padding:20px;">No featured products yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section recently-added-section">
            <div class="container">
                <div class="section-head">
                    <div class="section-head-left">
                        <h2 class="section-title">Recently Added</h2>
                        <div class="spacer"></div>
                        <div style="width:96px;height:2px;background:var(--primary)"></div>
                    </div>
                </div>

                <div class="grid-arrivals" data-arrivals-grid>
                    <?php if (!empty($latestProducts)): ?>
                        <?php foreach ($latestProducts as $index => $product): ?>
                            <?php
                            $productImage = ImageHelper::uploadUrl(
                                $product['main_image'] ?? '',
                                'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Product')
                            );
                            $regularPrice = (float) ($product['price'] ?? 0);
                            $salePrice = (float) ($product['sale_price'] ?? 0);
                            $hasDiscount = $regularPrice > 0 && $salePrice > 0 && $salePrice < $regularPrice;
                            $discountPercent = $hasDiscount
                                ? (int) round((($regularPrice - $salePrice) / $regularPrice) * 100)
                                : 0;
                            $displayPrice = $hasDiscount ? $salePrice : $regularPrice;
                            ?>
                            <article class="arrival-item <?= $index >= 12 ? 'is-hidden' : '' ?>" data-arrival-item>
                                <a class="arrival-image" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
                                    <?= ImageHelper::renderResponsivePicture(
                                        $product['main_image'] ?? '',
                                        $productImage,
                                        [
                                            'alt' => $product['title'] ?? 'Product',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'product_card'
                                    ) ?>
                                    <?php if ($hasDiscount): ?>
                                        <span class="arrival-badge discount">-<?= $discountPercent ?>%</span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['free_shipping'])): ?>
                                        <span class="arrival-badge shipping">Free Shipping</span>
                                    <?php endif; ?>
                                </a>
                                <span class="chip">Recently Added</span>
                                <h4><?= htmlspecialchars($product['title'] ?? 'Product') ?></h4>
                                <div class="arrival-price-row">
                                    <div class="arrival-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($displayPrice, 0) ?></div>
                                    <?php if ($hasDiscount): ?>
                                        <div class="arrival-old-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($regularPrice, 0) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?= renderHomeKokoTeaser($product, $settings, 'arrivals') ?>
                            </article>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div>No recently added products yet.</div>
                        <?php endif; ?>
                </div>
                <?php if (!empty($latestProducts) && count($latestProducts) > 12): ?>
                    <div class="load-more-wrap">
                        <button type="button" class="load-more-btn" data-arrivals-load-more>Load More</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <?php if (!empty($categoryTiles)): ?>
                    <div class="category-grid">
                        <?php foreach ($categoryTiles as $categoryTile): ?>
                            <?php
                            $tileImage = ImageHelper::uploadUrl(
                                $categoryTile['image'] ?? '',
                                'https://via.placeholder.com/900x1125?text=' . urlencode($categoryTile['name'] ?? 'Category')
                            );
                            ?>
                            <a class="category-card" href="<?= htmlspecialchars($baseUrl . 'shop/category/' . $categoryTile['id']) ?>" aria-label="<?= htmlspecialchars($categoryTile['name'] ?? 'Category') ?>">
                                <?= ImageHelper::renderResponsivePicture(
                                    $categoryTile['image'] ?? '',
                                    $tileImage,
                                    [
                                        'alt' => $categoryTile['name'] ?? 'Category',
                                        'loading' => 'lazy',
                                        'decoding' => 'async',
                                        'fetchpriority' => 'low'
                                    ],
                                    'product_gallery'
                                ) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="category-grid">
                        <div class="category-card"></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section section-dark sale-archive-section">
            <div class="container">
                <div class="section-head">
                    <div class="section-head-left">
                        <span class="label">Limited Time</span>
                        <h2 class="section-title">Special Offers</h2>
                        <p class="section-copy">Latest discounted products, sorted by newest arrivals.</p>
                    </div>
                    <div class="carousel-actions">
                        <button class="icon-btn" type="button" aria-label="Previous special offer products" data-sale-prev>←</button>
                        <button class="icon-btn" type="button" aria-label="Next special offer products" data-sale-next>→</button>
                    </div>
                </div>

                <div class="sale-rail hide-scrollbar" data-sale-rail>
                    <?php if (!empty($saleProducts)): ?>
                        <?php foreach ($saleProducts as $product): ?>
                            <?php
                            $productImage = ImageHelper::uploadUrl(
                                $product['main_image'] ?? '',
                                'https://via.placeholder.com/560x740?text=' . urlencode($product['title'] ?? 'Sale')
                            );
                            $regularPrice = (float) ($product['price'] ?? 0);
                            $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
                            $discount = ($regularPrice > 0 && $salePrice < $regularPrice)
                                ? (int) round((1 - ($salePrice / $regularPrice)) * 100)
                                : 0;
                            ?>
                            <article class="sale-card">
                                <a class="sale-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
                                    <?= ImageHelper::renderResponsivePicture(
                                        $product['main_image'] ?? '',
                                        $productImage,
                                        [
                                            'alt' => $product['title'] ?? 'Sale product',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'product_card'
                                    ) ?>
                                    <?php if ($discount > 0): ?>
                                        <span class="badge">-<?= $discount ?>%</span>
                                    <?php endif; ?>
                                </a>
                                <h4><?= htmlspecialchars($product['title'] ?? 'Product') ?></h4>
                                <div class="price-row">
                                    <span class="sale-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($salePrice, 0) ?></span>
                                    <?php if ($discount > 0): ?>
                                        <span class="old-price"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format($regularPrice, 0) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?= renderHomeKokoTeaser($product, $settings, 'sale') ?>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>No sale products yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const heroSlider = document.querySelector('[data-hero-slider]');
            const heroDots = document.querySelectorAll('[data-slide-to]');
            const productRail = document.querySelector('[data-product-rail]');
            const productPrev = document.querySelector('[data-product-prev]');
            const productNext = document.querySelector('[data-product-next]');
            const saleRail = document.querySelector('[data-sale-rail]');
            const salePrev = document.querySelector('[data-sale-prev]');
            const saleNext = document.querySelector('[data-sale-next]');

            let currentSlide = 0;
            let autoPlay;

            const goToSlide = (index) => {
                currentSlide = index;
                heroSlider.scrollTo({
                    left: heroSlider.clientWidth * index,
                    behavior: 'smooth'
                });
                heroDots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === index);
                });
            };

            const startAutoPlay = () => {
                clearInterval(autoPlay);
                if (!heroSlider || heroSlider.children.length < 2 || heroDots.length < 2) {
                    return;
                }
                autoPlay = setInterval(() => {
                    const nextSlide = (currentSlide + 1) % heroSlider.children.length;
                    goToSlide(nextSlide);
                }, 4000);
            };

            if (heroSlider && heroSlider.children.length >= 2) {
                heroSlider.addEventListener('scroll', () => {
                    const nextIndex = Math.round(heroSlider.scrollLeft / heroSlider.clientWidth);
                    if (nextIndex !== currentSlide) {
                        currentSlide = nextIndex;
                        heroDots.forEach((dot, dotIndex) => {
                            dot.classList.toggle('active', dotIndex === currentSlide);
                        });
                    }
                });

                heroSlider.addEventListener('touchstart', () => clearInterval(autoPlay), { passive: true });
                heroSlider.addEventListener('touchend', startAutoPlay, { passive: true });
                window.addEventListener('resize', () => goToSlide(currentSlide));

                heroDots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        const slideIndex = parseInt(dot.getAttribute('data-slide-to'), 10) || 0;
                        goToSlide(slideIndex);
                        startAutoPlay();
                    });
                });

                startAutoPlay();
            }

            if (productRail && productPrev && productNext) {
                const getScrollAmount = () => {
                    const productCard = productRail.querySelector('.product-card');
                    if (!productCard) {
                        return productRail.clientWidth * 0.9;
                    }

                    const railStyles = window.getComputedStyle(productRail);
                    const gapValue = parseFloat(railStyles.columnGap || railStyles.gap || '0') || 0;
                    return productCard.getBoundingClientRect().width + gapValue;
                };

                productPrev.addEventListener('click', () => {
                    productRail.scrollBy({
                        left: -getScrollAmount(),
                        behavior: 'smooth'
                    });
                });

                productNext.addEventListener('click', () => {
                    productRail.scrollBy({
                        left: getScrollAmount(),
                        behavior: 'smooth'
                    });
                });
            }





            if (saleRail && salePrev && saleNext) {
                const getSaleScrollAmount = () => {
                    const saleCard = saleRail.querySelector('.sale-card');
                    if (!saleCard) {
                        return saleRail.clientWidth * 0.9;
                    }
                    const railStyles = window.getComputedStyle(saleRail);
                    const gapValue = parseFloat(railStyles.columnGap || railStyles.gap || '0') || 0;
                    return saleCard.getBoundingClientRect().width + gapValue;
                };

                salePrev.addEventListener('click', () => {
                    saleRail.scrollBy({
                        left: -getSaleScrollAmount(),
                        behavior: 'smooth'
                    });
                });

                saleNext.addEventListener('click', () => {
                    saleRail.scrollBy({
                        left: getSaleScrollAmount(),
                        behavior: 'smooth'
                    });
                });
            }

            const arrivalsGrid = document.querySelector('[data-arrivals-grid]');
            const arrivalsLoadMore = document.querySelector('[data-arrivals-load-more]');

            if (arrivalsGrid && arrivalsLoadMore) {
                const arrivalItems = Array.from(arrivalsGrid.querySelectorAll('[data-arrival-item]'));
                const desktopInitial = 12;
                const mobileInitial = 4;
                const desktopBatch = 12;
                const mobileBatch = 4;
                const desktopBreakpoint = 1024;
                let currentMode = window.innerWidth >= desktopBreakpoint ? 'desktop' : 'mobile';
                let currentVisible = currentMode === 'desktop' ? desktopInitial : mobileInitial;

                const getBatchSize = () => currentMode === 'desktop' ? desktopBatch : mobileBatch;

                const renderArrivals = () => {
                    arrivalItems.forEach((item, index) => {
                        item.classList.toggle('is-hidden', index >= currentVisible);
                    });

                    arrivalsLoadMore.hidden = currentVisible >= arrivalItems.length;
                };

                arrivalsLoadMore.addEventListener('click', () => {
                    currentVisible = Math.min(arrivalItems.length, currentVisible + getBatchSize());
                    renderArrivals();
                });

                window.addEventListener('resize', () => {
                    const nextMode = window.innerWidth >= desktopBreakpoint ? 'desktop' : 'mobile';
                    if (nextMode === currentMode) {
                        return;
                    }

                    currentMode = nextMode;
                    currentVisible = currentMode === 'desktop' ? desktopInitial : mobileInitial;
                    renderArrivals();
                });

                renderArrivals();
            }
        });
    </script>
<?php customer_layout_end(); ?>
