<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';

$gridProducts = array_slice($products ?? [], 0, 24);
$productCount = count($products ?? []);
$isDiscountsPage = (($title ?? '') === 'Discounts!');
$discountsLimit = max(1, (int) ($discounts_limit ?? 20));
$discountsHasMore = !empty($discounts_has_more);
$discountsTotalProducts = max(0, (int) ($discounts_total_products ?? $productCount));
if ($isDiscountsPage) {
    $gridProducts = $products ?? [];
    $productCount = $discountsTotalProducts > 0 ? $discountsTotalProducts : count($gridProducts);
}
$currency = $settings['currency_symbol'] ?? 'LKR';
$productPrices = array_values(array_filter(array_map(static function ($prod) {
    $regular = isset($prod['price']) ? (float) $prod['price'] : 0;
    $sale = isset($prod['sale_price']) && (float) $prod['sale_price'] > 0 ? (float) $prod['sale_price'] : 0;
    return max($regular, $sale);
}, $products ?? []), static function ($price) {
    return $price > 0;
}));
$shopPriceFloor = !empty($productPrices) ? (int) floor(min($productPrices)) : 0;
$shopPriceCeiling = !empty($productPrices) ? (int) ceil(max($productPrices)) : 0;
$filterMinValue = $filter_min ?? null;
$filterMaxValue = $filter_max ?? null;
$selectedPriceMin = $filterMinValue !== null && $filterMinValue !== '' ? max($shopPriceFloor, (int) $filterMinValue) : null;
$selectedPriceMax = $filterMaxValue !== null && $filterMaxValue !== '' ? min($shopPriceCeiling, (int) $filterMaxValue) : null;
$priceSliderDefaultMin = $shopPriceFloor;
$priceSliderDefaultMax = $shopPriceCeiling;
$topLevelCategories = array_values(array_filter($categories ?? [], static function ($cat) {
    return empty($cat['parent_id']);
}));
$childCategoriesByParent = [];
foreach (($categories ?? []) as $cat) {
    if (!empty($cat['parent_id'])) {
        $childCategoriesByParent[(string) $cat['parent_id']][] = $cat;
    }
}
$selectedCategoryIds = array_map('strval', $filter_category_ids ?? []);
$activeFilterLabels = [];
if (!empty($search_query)) {
    $activeFilterLabels[] = 'Search: ' . $search_query;
}
if ($selectedPriceMin !== null || $selectedPriceMax !== null) {
    $activeFilterLabels[] = 'Price range';
}
if (!empty($selectedCategoryIds)) {
    $activeFilterLabels[] = count($selectedCategoryIds) . ' categories';
}

function shopPriceLabel(array $prod, string $currency): string
{
    $hasSale = !empty($prod['sale_price']) && (float) $prod['sale_price'] < (float) $prod['price'];
    if ($hasSale) {
        return $currency . ' ' . number_format((float) $prod['sale_price'], 0);
    }

    return $currency . ' ' . number_format((float) $prod['price'], 0);
}

function shopKokoTeaser(array $prod, array $settings, string $currency): string
{
    static $kokoLogoUrl = null;
    if ($kokoLogoUrl === null) {
        $kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
    }

    if (!KokoPricingHelper::isEnabled($settings)) {
        return '';
    }

    $basePrice = KokoPricingHelper::getEffectiveProductPrice($prod);
    if ($basePrice <= 0) {
        return '';
    }

    $teaser = KokoPricingHelper::getInstallmentData($basePrice, $settings);

    return '<div class="koko-installment-teaser" aria-label="KOKO installment plan">'
        . '<span class="koko-installment-text">or 3 x ' . htmlspecialchars($currency) . ' ' . number_format((float) $teaser['installment_amount'], 0) . '</span>'
        . '<img src="' . htmlspecialchars($kokoLogoUrl) . '" alt="KOKO" class="koko-installment-logo" style="height:16px;width:auto;flex-shrink:0;display:block;">'
        . '</div>';
}

function shopExcerpt(string $text, int $length = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $length, '...');
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, max(0, $length - 3)) . '...';
}

?>
<?php require_once 'views/layouts/customer_layout.php'; customer_layout_start(); ?>
<style>
        :root{--primary:var(--accent, #b68a2d);--primary-strong:var(--accent-red, #d4af37);--surface:#fcf9f8;--surface-low:#f6f3f2;--surface-mid:#f0eded;--surface-high:#eae7e7;--surface-highest:#e5e2e1;--ink:#1c1b1b;--muted:#6d6665;--shadow:0 24px 60px rgba(28,27,27,.08);--shadow-soft:0 14px 30px rgba(28,27,27,.06)}
        *{box-sizing:border-box} html{scroll-behavior:smooth;background:#fff} body{margin:0;font-family:"Manrope",sans-serif;background:#fff;color:var(--ink)} h1,h2,h3,h4,h5{font-family:"Noto Serif",serif;font-weight:400;margin:0} a{color:inherit;text-decoration:none} img{display:block;max-width:100%}
        .page{overflow-x:hidden;background:#fff;min-height:100vh}.container{width:min(1600px,calc(100% - 96px));margin:0 auto}
        .main{padding-top:0}
        .shop-page-shell{padding:34px 0 0;background:#fff}
        .shop-layout{display:grid;grid-template-columns:minmax(280px,360px) minmax(0,1fr);gap:30px;align-items:start}
        .shop-sidebar{position:sticky;top:104px;scroll-margin-top:104px;display:grid;gap:18px;padding:24px;border:1px solid rgba(28,27,27,.08);border-radius:0;background:#fff;box-shadow:var(--shadow-soft);backdrop-filter:blur(14px)}
        .shop-sidebar > summary{display:none;list-style:none;cursor:pointer;user-select:none}
        .shop-sidebar > summary::-webkit-details-marker{display:none}
        .shop-sidebar-trigger{display:flex;align-items:center;justify-content:space-between;gap:16px}
        .shop-sidebar-trigger strong{font-size:26px;line-height:1.1}
        .shop-sidebar-trigger span{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid rgba(28,27,27,.08);border-radius:0;background:#fff;font-size:12px;color:var(--ink)}
        .shop-sidebar-body{display:grid;gap:18px}
        .shop-sidebar-head{display:grid;gap:10px}
        .shop-sidebar-kicker{font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--primary)}
        .shop-sidebar-head h2{font-size:26px;line-height:1.1}
        .shop-sidebar-head p{margin:0;font-size:13px;line-height:1.8;color:var(--muted)}
        .shop-filter-form{display:grid;gap:16px}
        .shop-filter-section{padding-top:16px;border-top:1px solid rgba(28,27,27,.08);display:grid;gap:12px}
        .shop-filter-section:first-of-type{padding-top:0;border-top:0}
        .shop-filter-label{font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--primary)}
        .shop-filter-input,.shop-filter-select{width:100%;height:48px;padding:0 14px;border:1px solid rgba(28,27,27,.1);border-radius:0;background:#fff;color:var(--ink);font:inherit;outline:none;transition:border-color .2s ease,box-shadow .2s ease}
        .shop-filter-input:focus,.shop-filter-select:focus{border-color:rgba(182,138,45,.38);box-shadow:0 0 0 3px rgba(182,138,45,.12)}
        .shop-price-range{display:grid;gap:12px}
        .shop-price-range-values{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:10px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:rgba(28,27,27,.58)}
        .shop-price-range-track{position:relative;height:34px;display:flex;align-items:center}
        .shop-price-range-line{position:absolute;left:0;right:0;height:2px;background:rgba(28,27,27,.14)}
        .shop-price-range-fill{position:absolute;height:2px;background:var(--primary);left:0;right:0}
        .shop-price-range input[type="range"]{position:absolute;left:0;width:100%;height:34px;margin:0;background:transparent;pointer-events:none;-webkit-appearance:none;appearance:none}
        .shop-price-range input[type="range"]::-webkit-slider-runnable-track{height:2px;background:transparent;border:0}
        .shop-price-range input[type="range"]::-moz-range-track{height:2px;background:transparent;border:0}
        .shop-price-range input[type="range"]::-webkit-slider-thumb{pointer-events:auto;-webkit-appearance:none;appearance:none;width:18px;height:18px;border:1px solid rgba(28,27,27,.14);border-radius:0;background:#fff;box-shadow:0 4px 10px rgba(28,27,27,.14);margin-top:-8px;cursor:pointer}
        .shop-price-range input[type="range"]::-moz-range-thumb{pointer-events:auto;width:18px;height:18px;border:1px solid rgba(28,27,27,.14);border-radius:0;background:#fff;box-shadow:0 4px 10px rgba(28,27,27,.14);cursor:pointer}
        .shop-price-range input[type="range"]:focus::-webkit-slider-thumb{border-color:rgba(182,138,45,.55);box-shadow:0 0 0 3px rgba(182,138,45,.14),0 4px 10px rgba(28,27,27,.14)}
        .shop-price-range input[type="range"]:focus::-moz-range-thumb{border-color:rgba(182,138,45,.55);box-shadow:0 0 0 3px rgba(182,138,45,.14),0 4px 10px rgba(28,27,27,.14)}
        .shop-category-list{display:grid;gap:10px;max-height:300px;overflow:auto;padding-right:4px}
        .shop-category-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:0;border:1px solid rgba(28,27,27,.06);background:#fff;font-size:13px;line-height:1.4}
        .shop-category-item input{accent-color:var(--primary)}
        .shop-category-item strong{font-weight:700}
        .shop-category-item small{margin-left:auto;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
        .shop-category-children{display:grid;gap:10px;padding-left:16px;border-left:1px dashed rgba(28,27,27,.14)}
        .shop-shortcut-list{display:grid;gap:10px}
        .shop-shortcut-link{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:0;border:1px solid rgba(28,27,27,.08);background:#fff}
        .shop-shortcut-link span{font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .shop-shortcut-link small{font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
        .shop-filter-actions{display:flex;gap:10px;flex-wrap:wrap}
        .shop-filter-submit,.shop-filter-reset{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 18px;border-radius:0;border:0;font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;cursor:pointer}
        .shop-filter-submit{background:var(--ink);color:#fff}
        .shop-filter-reset{border:1px solid rgba(28,27,27,.1);background:rgba(255,255,255,.82);color:var(--ink)}
        .shop-content{min-width:0}
        .shop-content-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:0 0 24px}
        .shop-content-head h1{font-size:32px;line-height:1.1}
        .shop-content-meta{display:grid;gap:8px;justify-items:end;text-align:right}
        .shop-content-count{font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--primary)}
        .shop-content-note{font-size:13px;line-height:1.7;color:var(--muted);max-width:34ch}
        .shop-active-filters{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .shop-active-chip{display:inline-flex;align-items:center;justify-content:center;padding:7px 10px;border-radius:0;border:1px solid rgba(28,27,27,.08);background:#fff;font-size:9px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:rgba(28,27,27,.62)}
        .shop-grid{padding:0 0 68px;background:#fff}
        .product-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:24px 22px;align-items:start}
        .product-card{display:grid;gap:12px;align-content:start;height:100%;padding-bottom:16px;background:#fff;box-shadow:var(--shadow-soft);overflow:hidden}
        .product-media{position:relative;aspect-ratio:4/5;overflow:hidden;background:var(--surface-high)}
        .product-media img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease}
        .product-card:hover .product-media img{transform:scale(1.04)}
        .badge{position:absolute;top:14px;right:14px;background:#d4af37;color:#fff;padding:8px 12px;font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .badge.alt{left:16px;right:auto;background:var(--ink)}
        .product-meta{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:start;padding:0 16px}
        .product-name{font-size:17px;line-height:1.22;font-family:sans-serif;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.5em}
        .product-category{font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:var(--muted);margin-top:0}
        .shop-price-row{display:flex;align-items:baseline;gap:8px;font-size:16px;font-weight:800;white-space:nowrap}
        .shop-sale-price{color:#d4af37}
        .shop-regular-price{color:rgba(28,27,27,.42);text-decoration:line-through;font-size:13px;font-weight:700}
        .product-price{font-size:12px;font-weight:800;white-space:nowrap;color:var(--primary)}
        .product-desc{color:var(--muted);font-size:13px;line-height:1.65;margin:0;padding:0 16px;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;min-height:6.6em}
        .is-discounts-page .shop-content-head h1{font-family:sans-serif !important}
        .is-discounts-page .product-name{font-family:sans-serif !important;-webkit-line-clamp:2;min-height:2.5em}
        .is-discounts-page .product-desc{-webkit-line-clamp:4;min-height:6.6em}
        .is-discounts-page .product-media{aspect-ratio:1/1}
        .is-discounts-page .shop-price-row{font-size:18px}
        .is-discounts-page .shop-sale-price{font-size:18px}
        .is-discounts-page .shop-regular-price{font-size:14px}
        .discounts-infinite-loader{display:none;text-align:center;padding:16px 8px 4px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8a8383}
        .product-card .koko-installment-teaser{display:flex;align-items:center;flex-direction:row;margin-top:0;padding:0 16px;gap:6px;flex-wrap:nowrap;white-space:nowrap;overflow:hidden;min-width:0}
        .product-card .koko-installment-text{min-width:0;overflow:hidden;text-overflow:ellipsis}
        .product-card .koko-installment-logo{height:16px;width:auto;flex-shrink:0;display:block}
        .pagination-wrap{padding:28px 0 0;display:grid;justify-items:center;gap:18px}
        .pagination-topline{width:100%;height:1px;background:rgba(28,27,27,.08)}
        .pagination-row{display:flex;align-items:center;gap:20px;flex-wrap:wrap;justify-content:center}
        .pagination-button{display:inline-flex;align-items:center;gap:10px;background:transparent;border:0;color:rgba(28,27,27,.46);font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;cursor:pointer}
        .pagination-button.active{color:var(--primary)}
        .load-more{min-height:52px;padding:0 28px;border:0;background:var(--ink);color:#fff;font-size:10px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;cursor:pointer}
        .empty-state{padding:56px 24px;text-align:center;background:#fff;box-shadow:var(--shadow-soft)}.empty-state h3{font-size:24px;margin-bottom:10px}.empty-state p{margin:0;color:var(--muted);line-height:1.8}
        @media (max-width:1180px){.container{width:min(100% - 48px,1600px)}.shop-layout{grid-template-columns:1fr}.shop-sidebar{position:relative;top:auto}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:760px){.container{width:100%}.main{padding-top:0}.shop-page-shell{padding-top:22px}.shop-sidebar{padding:0;border-radius:0;position:relative;top:auto}.shop-sidebar > summary{display:block;padding:18px 20px;border:1px solid rgba(28,27,27,.08);background:rgba(255,255,255,.72)}.shop-sidebar:not([open]) .shop-sidebar-body{display:none}.shop-sidebar-body{padding:18px 20px 20px;border:1px solid rgba(28,27,27,.08);border-top:0}.shop-sidebar-trigger strong{font-size:20px}.shop-sidebar-trigger span{width:30px;height:30px}.shop-content-head{flex-direction:column;align-items:flex-start}.shop-content-meta{justify-items:start;text-align:left}.shop-active-filters{justify-content:flex-start}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}}
    </style>
    <main class="main">
        <div class="shop-page-shell">
            <div class="container shop-layout">
                <details class="shop-sidebar" id="shopSidebar" open>
                    <summary aria-label="Toggle shop filters">
                        <div class="shop-sidebar-trigger">
                            <strong>Filters</strong>
                            <span aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                        </div>
                    </summary>
                    <div class="shop-sidebar-body">
                        <div class="shop-sidebar-head">
                            <span class="shop-sidebar-kicker">Refine your search</span>
                            <h2><?= htmlspecialchars($title ?? 'Collections') ?></h2>
                            <p>Browse by category, tighten the price range, or jump into featured shop edits without leaving the page.</p>
                        </div>

                        <form class="shop-filter-form" method="get" action="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>">
                        <div class="shop-filter-section">
                            <label class="shop-filter-label" for="shop-search">Search</label>
                            <input class="shop-filter-input" id="shop-search" type="search" name="search" value="<?= htmlspecialchars((string) ($filter_search ?? $search_query ?? '')) ?>" placeholder="Search products">
                        </div>

                        <div class="shop-filter-section">
                            <label class="shop-filter-label">Price Range</label>
                            <div class="shop-price-range" data-price-range
                                data-default-min="<?= (int) $priceSliderDefaultMin ?>"
                                data-default-max="<?= (int) $priceSliderDefaultMax ?>">
                                <div class="shop-price-range-values">
                                    <span data-price-min-label><?= htmlspecialchars($currency) ?> <?= number_format((float) ($selectedPriceMin ?? $priceSliderDefaultMin), 0) ?></span>
                                    <span data-price-max-label><?= htmlspecialchars($currency) ?> <?= number_format((float) ($selectedPriceMax ?? $priceSliderDefaultMax), 0) ?></span>
                                </div>
                                <div class="shop-price-range-track">
                                    <div class="shop-price-range-line"></div>
                                    <div class="shop-price-range-fill" data-price-range-fill></div>
                                    <input type="range" min="<?= (int) $priceSliderDefaultMin ?>" max="<?= (int) $priceSliderDefaultMax ?>" step="1" value="<?= (int) ($selectedPriceMin ?? $priceSliderDefaultMin) ?>" aria-label="Minimum price" data-price-min-slider>
                                    <input type="range" min="<?= (int) $priceSliderDefaultMin ?>" max="<?= (int) $priceSliderDefaultMax ?>" step="1" value="<?= (int) ($selectedPriceMax ?? $priceSliderDefaultMax) ?>" aria-label="Maximum price" data-price-max-slider>
                                </div>
                                <input type="hidden" name="min" value="<?= htmlspecialchars((string) ($selectedPriceMin ?? '')) ?>" data-price-min-input>
                                <input type="hidden" name="max" value="<?= htmlspecialchars((string) ($selectedPriceMax ?? '')) ?>" data-price-max-input>
                            </div>
                        </div>

                        <div class="shop-filter-section">
                            <label class="shop-filter-label">Categories</label>
                            <div class="shop-category-list">
                                <?php if (!empty($topLevelCategories)): ?>
                                    <?php foreach ($topLevelCategories as $cat): ?>
                                        <?php
                                        $catId = (string) ($cat['id'] ?? '');
                                        $catName = (string) ($cat['name'] ?? 'Category');
                                        $catChildren = $childCategoriesByParent[$catId] ?? [];
                                        ?>
                                        <label class="shop-category-item">
                                            <input type="checkbox" name="cat[]" value="<?= htmlspecialchars($catId) ?>" <?= in_array($catId, $selectedCategoryIds, true) ? 'checked' : '' ?>>
                                            <span><strong><?= htmlspecialchars($catName) ?></strong></span>
                                            <small><?= !empty($catChildren) ? 'Parent' : 'Category' ?></small>
                                        </label>
                                        <?php if (!empty($catChildren)): ?>
                                            <div class="shop-category-children">
                                                <?php foreach ($catChildren as $childCat): ?>
                                                    <?php
                                                    $childId = (string) ($childCat['id'] ?? '');
                                                    $childName = (string) ($childCat['name'] ?? 'Category');
                                                    ?>
                                                    <label class="shop-category-item">
                                                        <input type="checkbox" name="cat[]" value="<?= htmlspecialchars($childId) ?>" <?= in_array($childId, $selectedCategoryIds, true) ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars($childName) ?></span>
                                                        <small>Sub</small>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state" style="padding:20px 16px;background:rgba(255,255,255,.58);box-shadow:none;text-align:left;">
                                        <h3 style="font-size:18px;margin-bottom:6px;">No categories</h3>
                                        <p style="margin:0;">Categories will appear here once they are added in the admin panel.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="shop-filter-section">
                            <label class="shop-filter-label">Quick Shop</label>
                            <div class="shop-shortcut-list">
                                <a class="shop-shortcut-link" href="<?= htmlspecialchars($baseUrl . 'shop/featured') ?>">
                                    <span>Featured</span>
                                    <small>Editor picks</small>
                                </a>
                                <a class="shop-shortcut-link" href="<?= htmlspecialchars($baseUrl . 'shop/sales') ?>">
                                    <span>Discounts</span>
                                    <small>Sale items</small>
                                </a>
                            </div>
                        </div>

                        <div class="shop-filter-actions">
                            <button class="shop-filter-submit" type="submit">Apply Filters</button>
                            <a class="shop-filter-reset" href="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>">Reset</a>
                        </div>
                        </form>
                    </div>
                </details>

                <section class="shop-content<?= $isDiscountsPage ? ' is-discounts-page' : '' ?>">
                    <div class="shop-content-head">
                        <div>
                            <h1><?= htmlspecialchars($title === 'Shop' ? 'Collections' : ($title ?? 'Collections')) ?></h1>
                            <div class="shop-content-note">Discover our curated beauty collection with premium essentials, signature favorites, and fresh arrivals selected for your daily routine.</div>
                        </div>
                        <div class="shop-content-meta">
                            <div class="shop-content-count"><?= (int) $productCount ?> products</div>
                            <?php if (!empty($activeFilterLabels)): ?>
                                <div class="shop-active-filters">
                                    <?php foreach ($activeFilterLabels as $filterLabel): ?>
                                        <span class="shop-active-chip"><?= htmlspecialchars($filterLabel) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="shop-grid">
                        <?php if (!empty($gridProducts)): ?>
                            <div class="product-grid<?= $isDiscountsPage ? ' discounts-grid' : '' ?>"<?= $isDiscountsPage ? ' id="discountsGrid" data-limit="' . (int) $discountsLimit . '" data-next-offset="' . count($gridProducts) . '" data-has-more="' . ($discountsHasMore ? '1' : '0') . '"' : '' ?>>
                                <?php foreach ($gridProducts as $prod): ?>
                                    <?php
                                    $imagePath = ImageHelper::uploadUrl(
                                        $prod['main_image'] ?? '',
                                        'https://via.placeholder.com/700x875?text=' . urlencode($prod['title'] ?? 'Product')
                                    );
                                    $isOnSale = !empty($prod['sale_price']) && (float) $prod['sale_price'] < (float) $prod['price'];
                                    ?>
                                    <article class="product-card">
                                        <a class="product-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . ($prod['id'] ?? '')) ?>">
                                            <?= ImageHelper::renderResponsivePicture(
                                                $prod['main_image'] ?? '',
                                                $imagePath,
                                                [
                                                    'alt' => $prod['title'] ?? 'Product',
                                                    'loading' => 'lazy',
                                                    'decoding' => 'async',
                                                    'fetchpriority' => 'low'
                                                ],
                                                'product_card'
                                            ) ?>
                                            <?php if ($isOnSale): ?>
                                                <span class="badge">Sale</span>
                                            <?php elseif (!empty($prod['free_shipping'])): ?>
                                                <span class="badge">Free Shipping</span>
                                            <?php endif; ?>
                                        </a>
                                        <div class="product-meta">
                                            <div>
                                                <div class="product-category"><?= htmlspecialchars($prod['parent_category_name'] ?? $prod['category_name'] ?? 'Shop') ?></div>
                                                <h3 class="product-name">
                                                    <a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . ($prod['id'] ?? '')) ?>"><?= htmlspecialchars($prod['title'] ?? 'Product') ?></a>
                                                </h3>
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
                                        <?= shopKokoTeaser($prod, $settings ?? [], $currency) ?>
                                        <p class="product-desc"><?= htmlspecialchars(shopExcerpt((string) ($prod['short_description'] ?? $prod['description'] ?? 'Fresh product from the new shop page.'), 120)) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($isDiscountsPage): ?>
                                <div id="discountsInfiniteLoader" class="discounts-infinite-loader" style="<?= $discountsHasMore ? 'display:block;' : 'display:none;' ?>">Loading more products...</div>
                                <div id="discountsInfiniteSentinel" style="<?= $discountsHasMore ? '' : 'display:none;' ?>" aria-hidden="true"></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No products found</h3>
                                <p>Try adjusting the search, price range, or selected category.</p>
                            </div>
                        <?php endif; ?>

                        <div class="pagination-wrap"<?= $isDiscountsPage ? ' style="display:none;"' : '' ?>>
                            <div class="pagination-topline"></div>
                            <div class="pagination-row" aria-label="Pagination">
                                <button class="pagination-button" type="button"><i class="fas fa-chevron-left"></i> Previous</button>
                                <div class="pagination-row">
                                    <span class="pagination-button active">01</span>
                                    <span class="pagination-button">02</span>
                                    <span class="pagination-button">03</span>
                                    <span class="pagination-button">...</span>
                                    <span class="pagination-button">12</span>
                                </div>
                                <button class="pagination-button" type="button">Next <i class="fas fa-chevron-right"></i></button>
                            </div>
                            <button class="load-more" type="button">Load More Items</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('#shopSidebar');
        const priceRange = document.querySelector('[data-price-range]');

        let isMobileViewport = window.innerWidth <= 760;

        const applySidebarState = (shouldBeMobile) => {
            if (!sidebar) {
                return;
            }

            if (shouldBeMobile) {
                sidebar.open = false;
            } else {
                sidebar.open = true;
            }
        };

        applySidebarState(isMobileViewport);

        window.addEventListener('resize', function () {
            const nextIsMobileViewport = window.innerWidth <= 760;
            if (nextIsMobileViewport === isMobileViewport) {
                return;
            }

            isMobileViewport = nextIsMobileViewport;
            applySidebarState(nextIsMobileViewport);
        });

        if (priceRange) {
            const minSlider = priceRange.querySelector('[data-price-min-slider]');
            const maxSlider = priceRange.querySelector('[data-price-max-slider]');
            const minInput = priceRange.querySelector('[data-price-min-input]');
            const maxInput = priceRange.querySelector('[data-price-max-input]');
            const minLabel = priceRange.querySelector('[data-price-min-label]');
            const maxLabel = priceRange.querySelector('[data-price-max-label]');
            const fill = priceRange.querySelector('[data-price-range-fill]');
            const defaultMin = parseInt(priceRange.dataset.defaultMin || '0', 10);
            const defaultMax = parseInt(priceRange.dataset.defaultMax || '0', 10);
            const currencySymbol = <?= json_encode($currency) ?>;

            const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
            const formatCurrency = (value) => `${currencySymbol} ${Number(value || 0).toLocaleString('en-US')}`;

            const updateUI = () => {
                if (!minSlider || !maxSlider || !minInput || !maxInput || !fill || !minLabel || !maxLabel) {
                    return;
                }

                let minValue = parseInt(minSlider.value, 10);
                let maxValue = parseInt(maxSlider.value, 10);

                if (minValue > maxValue) {
                    if (document.activeElement === minSlider) {
                        maxValue = minValue;
                        maxSlider.value = String(maxValue);
                    } else {
                        minValue = maxValue;
                        minSlider.value = String(minValue);
                    }
                }

                const range = Math.max(1, defaultMax - defaultMin);
                const minPct = ((minValue - defaultMin) / range) * 100;
                const maxPct = ((maxValue - defaultMin) / range) * 100;

                fill.style.left = `${clamp(minPct, 0, 100)}%`;
                fill.style.right = `${clamp(100 - maxPct, 0, 100)}%`;
                minLabel.textContent = formatCurrency(minValue);
                maxLabel.textContent = formatCurrency(maxValue);

                const usingDefaultMin = minValue === defaultMin;
                const usingDefaultMax = maxValue === defaultMax;

                minInput.value = usingDefaultMin ? '' : String(minValue);
                maxInput.value = usingDefaultMax ? '' : String(maxValue);
            };

            if (minSlider && maxSlider) {
                minSlider.addEventListener('input', updateUI);
                maxSlider.addEventListener('input', updateUI);
                updateUI();
            }
        }

        <?php if ($isDiscountsPage): ?>
        const discountsGrid = document.getElementById('discountsGrid');
        const discountsLoader = document.getElementById('discountsInfiniteLoader');
        const discountsSentinel = document.getElementById('discountsInfiniteSentinel');
        if (discountsGrid && discountsLoader && discountsSentinel) {
            let isLoadingDiscounts = false;
            let hasMoreDiscounts = discountsGrid.dataset.hasMore === '1';
            let nextDiscountsOffset = parseInt(discountsGrid.dataset.nextOffset || '0', 10) || 0;
            const discountsLimit = parseInt(discountsGrid.dataset.limit || '20', 10) || 20;

            const hideDiscountsInfiniteUi = function () {
                discountsLoader.style.display = 'none';
                discountsSentinel.style.display = 'none';
            };

            if (!hasMoreDiscounts) {
                hideDiscountsInfiniteUi();
            } else {
                const loadMoreDiscounts = async function () {
                    if (isLoadingDiscounts || !hasMoreDiscounts) return;
                    isLoadingDiscounts = true;
                    discountsLoader.style.display = 'block';

                    try {
                        const url = <?= json_encode($baseUrl . 'shop/discountsLoadMore') ?> + '?offset=' + encodeURIComponent(String(nextDiscountsOffset)) + '&limit=' + encodeURIComponent(String(discountsLimit));
                        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const payload = await response.json();

                        if (!payload || !payload.success) throw new Error('Unable to load more products');

                        if (payload.html && String(payload.html).trim() !== '') {
                            discountsSentinel.insertAdjacentHTML('beforebegin', payload.html);
                        }

                        nextDiscountsOffset = Number(payload.next_offset || nextDiscountsOffset);
                        hasMoreDiscounts = !!payload.has_more;
                        discountsGrid.dataset.nextOffset = String(nextDiscountsOffset);
                        discountsGrid.dataset.hasMore = hasMoreDiscounts ? '1' : '0';

                        if (!hasMoreDiscounts || Number(payload.count || 0) === 0) {
                            hideDiscountsInfiniteUi();
                        }
                    } catch (error) {
                        discountsLoader.textContent = 'Unable to load more products';
                    } finally {
                        isLoadingDiscounts = false;
                    }
                };

                const discountsObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) loadMoreDiscounts();
                    });
                }, { rootMargin: '300px 0px 300px 0px' });

                discountsObserver.observe(discountsSentinel);
            }
        }
        <?php endif; ?>

    });
</script>
<?php customer_layout_end(); ?>
