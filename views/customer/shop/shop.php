<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';
require_once 'views/layouts/customer_layout.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$shopName = !empty($settings['shop_name']) ? (string) $settings['shop_name'] : 'STYLE1';
$currency = (string) ($settings['currency_symbol'] ?? 'LKR');
$kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
$shopProducts = array_values(array_filter($products ?? [], static function ($product) {
    return !empty($product['id']);
}));
$shopCategories = array_values(array_filter($categories ?? [], static function ($category) {
    return !empty($category['id']);
}));
$shopCount = count($shopProducts);
$shopStoreTotalCount = max(0, (int) ($shop_store_total_products ?? $shopCount));
$shopLimit = max(1, (int) ($shop_limit ?? 20));
$shopHasMore = !empty($shop_has_more);
$filterSearch = trim((string) ($filter_search ?? ''));
$filterCategory = (string) ($filter_category ?? '');

customer_layout_start();
?>

<style>
    .shop-page{
        background:#fff;
        color:#1c1b1b;
        padding:34px 0 96px;
    }

    .shop-shell{
        width:min(1600px,calc(100% - 96px));
        margin:0 auto;
    }

    .shop-hero{
        padding:8px 0 28px;
    }

    .shop-head{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:24px;
        margin-bottom:28px;
    }

    .shop-head-left{
        max-width:760px;
    }

    .shop-kicker{
        display:block;
        margin-bottom:8px;
        font-size:11px;
        letter-spacing:.26em;
        text-transform:uppercase;
        color:var(--primary);
        font-weight:800;
    }

    .shop-title{
        margin:0;
        font-family:sans-serif;
        font-size:clamp(34px,4vw,54px);
        line-height:1.02;
        letter-spacing:-.04em;
    }

    .shop-copy{
        margin:10px 0 0;
        max-width:64ch;
        color:#6d6665;
        line-height:1.8;
        font-size:15px;
    }

    .shop-count{
        font-size:10px;
        font-weight:800;
        letter-spacing:.2em;
        text-transform:uppercase;
        color:var(--primary);
        white-space:nowrap;
    }

    .shop-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:40px 24px;
        align-items:start;
    }
    .shop-filter{
        margin:0 0 26px;
        padding:16px;
        border:1px solid rgba(28,27,27,.1);
        background:#fff;
    }
    .shop-filter-form{
        display:grid;
        grid-template-columns:2fr 1fr auto auto;
        gap:10px;
        align-items:end;
    }
    .shop-filter-field label{
        display:block;
        margin-bottom:6px;
        font-size:10px;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:#8a8380;
        font-weight:800;
    }
    .shop-filter-field input,.shop-filter-field select{
        width:100%;
        height:42px;
        border:1px solid rgba(28,27,27,.14);
        background:#fff;
        color:#1c1b1b;
        padding:0 12px;
        font:inherit;
    }
    .shop-filter-btn{
        height:42px;
        border:0;
        padding:0 16px;
        background:#1c1b1b;
        color:#fff;
        font-size:10px;
        letter-spacing:.16em;
        text-transform:uppercase;
        font-weight:800;
        cursor:pointer;
    }
    .shop-filter-reset{
        height:42px;
        border:1px solid rgba(28,27,27,.14);
        padding:0 16px;
        background:#fff;
        color:#1c1b1b;
        font-size:10px;
        letter-spacing:.16em;
        text-transform:uppercase;
        font-weight:800;
        display:inline-flex;
        align-items:center;
        text-decoration:none;
    }

    .shop-card{
        display:grid;
        gap:12px;
        align-content:start;
        height:100%;
    }

    .shop-media{
        position:relative;
        aspect-ratio:4/5;
        overflow:hidden;
        background:#f2efee;
    }

    .shop-media img{
        width:100%;
        height:100%;
        object-fit:cover;
        transition:transform .7s ease;
    }

    .shop-card:hover .shop-media img{
        transform:scale(1.04);
    }

    .shop-badge{
        position:absolute;
        top:16px;
        right:16px;
        background:#d4af37;
        color:#fff;
        padding:8px 12px;
        font-size:10px;
        font-weight:800;
        letter-spacing:.16em;
        text-transform:uppercase;
    }

    .shop-body{
        display:grid;
        gap:8px;
        align-content:start;
    }

    .shop-chip{
        font-size:10px;
        letter-spacing:.22em;
        text-transform:uppercase;
        color:#8a8380;
    }

    .shop-name{
        margin:0;
        font-family:sans-serif;
        font-size:18px;
        line-height:1.25;
        letter-spacing:-.02em;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
        text-overflow:ellipsis;
        min-height:2.5em;
    }

    .shop-name a{
        color:inherit;
    }

    .shop-price-row{
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
        font-size:16px;
    }

    .shop-sale-price{
        font-size:16px;
        font-weight:800;
        letter-spacing:.1em;
        text-transform:uppercase;
        color:#d4af37;
    }

    .shop-old-price{
        font-size:13px;
        font-weight:700;
        color:#8c8785;
        text-decoration:line-through;
    }

    .shop-koko-teaser{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:nowrap;
        white-space:nowrap;
        overflow:hidden;
        min-width:0;
        margin-top:2px;
    }

    .shop-koko-text{
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#6d6665;
    }

    .shop-koko-logo{
        height:16px;
        width:auto;
        flex-shrink:0;
        display:block;
    }

    .shop-desc{
        margin:0;
        color:#6d6665;
        font-size:13px;
        line-height:1.7;
        display:-webkit-box;
        -webkit-line-clamp:4;
        -webkit-box-orient:vertical;
        overflow:hidden;
        min-height:6.8em;
    }

    .shop-empty{
        grid-column:1 / -1;
        padding:48px 24px;
        text-align:center;
        background:#fff;
        box-shadow:0 14px 30px rgba(28,27,27,.06);
    }

    .shop-empty h3{
        margin:0 0 8px;
        font-size:24px;
    }

    .shop-empty p{
        margin:0;
        color:#6d6665;
        line-height:1.8;
    }

    .shop-infinite-loader{
        grid-column:1 / -1;
        text-align:center;
        font-size:12px;
        letter-spacing:.14em;
        text-transform:uppercase;
        color:#8a8380;
        padding:16px 8px 4px;
    }

    @media (max-width: 1180px){
        .shop-shell{
            width:min(100% - 48px,1600px);
        }

        .shop-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
        .shop-filter-form{
            grid-template-columns:1fr 1fr;
        }
    }

    @media (max-width: 760px){
        .shop-page{
            padding:22px 0 72px;
        }

        .shop-shell{
            width:min(100% - 28px,1600px);
        }

        .shop-head{
            flex-direction:column;
            align-items:flex-start;
            gap:10px;
        }

        .shop-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:28px 14px;
        }
        .shop-filter{
            padding:12px;
            margin-bottom:18px;
        }
        .shop-filter-form{
            grid-template-columns:1fr;
        }

        .shop-badge{
            top:10px;
            right:10px;
            padding:6px 9px;
            font-size:9px;
        }

        .shop-name{
            font-size:15px;
            min-height:1.25em;
        }

        .shop-sale-price{font-size:14px}
        .shop-old-price{font-size:12px}

        .shop-desc{
            font-size:12px;
            line-height:1.55;
            min-height:4.96em;
        }
    }
</style>

<main class="shop-page">
    <div class="shop-shell">
        <section class="shop-hero">
            <div class="shop-head">
                <div class="shop-head-left">
                    <span class="shop-kicker"><?= htmlspecialchars($shopName) ?> New Arrivals</span>
                    <h1 class="shop-title">Shop</h1>
                    <p class="shop-copy">Discover our curated beauty collection with premium essentials, signature favorites, and fresh arrivals selected for your daily routine.</p>
                </div>
                <div class="shop-count"><?= (int) $shopStoreTotalCount ?> products</div>
            </div>
        </section>

        <section class="shop-filter" aria-label="Shop filters">
            <form class="shop-filter-form" method="get" action="<?= htmlspecialchars($baseUrl . 'shop') ?>">
                <div class="shop-filter-field">
                    <label for="shopSearch">Search</label>
                    <input type="search" id="shopSearch" name="search" value="<?= htmlspecialchars($filterSearch) ?>" placeholder="Search products">
                </div>
                <div class="shop-filter-field">
                    <label for="shopCategory">Category</label>
                    <select id="shopCategory" name="category">
                        <option value="">All</option>
                        <?php foreach ($shopCategories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $filterCategory === (string) (int) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) ($category['name'] ?? 'Category')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="shop-filter-btn">Apply</button>
                <a href="<?= htmlspecialchars($baseUrl . 'shop') ?>" class="shop-filter-reset">Reset</a>
            </form>
        </section>

        <section class="shop-grid" id="shopGrid" aria-label="Shop products" data-limit="<?= (int) $shopLimit ?>" data-next-offset="<?= (int) $shopCount ?>" data-has-more="<?= $shopHasMore ? '1' : '0' ?>">
            <?php if (!empty($shopProducts)): ?>
                <?php foreach ($shopProducts as $product): ?>
                    <?php
                    $regularPrice = (float) ($product['price'] ?? 0);
                    $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
                    $hasDiscount = ($regularPrice > 0 && $salePrice > 0 && $salePrice < $regularPrice);
                    $discount = $hasDiscount
                        ? (int) round((1 - ($salePrice / $regularPrice)) * 100)
                        : 0;
                    $productImage = ImageHelper::uploadUrl(
                        $product['main_image'] ?? '',
                        'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Product')
                    );
                    $description = trim((string) ($product['short_description'] ?? $product['description'] ?? ''));
                    ?>
                    <article class="shop-card">
                        <a class="shop-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
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
                            <?php if ($hasDiscount && $discount > 0): ?>
                                <span class="shop-badge">-<?= $discount ?>%</span>
                            <?php endif; ?>
                            <?php if (!empty($product['free_shipping'])): ?>
                                <span class="shop-badge alt">Free Shipping</span>
                            <?php endif; ?>
                        </a>

                        <div class="shop-body">
                            <span class="shop-chip"><?= htmlspecialchars($product['parent_category_name'] ?? $product['category_name'] ?? 'Shop') ?></span>
                            <h2 class="shop-name">
                                <a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>"><?= htmlspecialchars($product['title'] ?? 'Product') ?></a>
                            </h2>

                            <div class="shop-price-row">
                                <span class="shop-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($hasDiscount ? $salePrice : $regularPrice, 0) ?></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="shop-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (KokoPricingHelper::isEnabled($settings ?? [])): ?>
                                <?php
                                $kokoBasePrice = KokoPricingHelper::getEffectiveProductPrice($product);
                                if ($kokoBasePrice > 0) {
                                    $kokoTeaser = KokoPricingHelper::getInstallmentData($kokoBasePrice, $settings ?? []);
                                } else {
                                    $kokoTeaser = null;
                                }
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="shop-empty">
                    <h3>No products yet</h3>
                    <p>When products are added and marked active, they will appear here automatically.</p>
                </div>
            <?php endif; ?>
            <div id="shopInfiniteLoader" class="shop-infinite-loader" style="<?= $shopHasMore ? '' : 'display:none;' ?>">Loading more products...</div>
            <div id="shopInfiniteSentinel" style="<?= $shopHasMore ? '' : 'display:none;' ?>" aria-hidden="true"></div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('shopGrid');
    const loader = document.getElementById('shopInfiniteLoader');
    const sentinel = document.getElementById('shopInfiniteSentinel');
    if (!grid || !loader || !sentinel) return;

    let isLoading = false;
    let hasMore = grid.dataset.hasMore === '1';
    let nextOffset = parseInt(grid.dataset.nextOffset || '0', 10) || 0;
    const limit = parseInt(grid.dataset.limit || '20', 10) || 20;

    const hideInfiniteUi = function () {
        loader.style.display = 'none';
        sentinel.style.display = 'none';
    };

    if (!hasMore) {
        hideInfiniteUi();
        return;
    }

    const loadMore = async function () {
        if (isLoading || !hasMore) return;
        isLoading = true;
        loader.style.display = 'block';

        try {
            const currentParams = new URLSearchParams(window.location.search);
            const filteredParams = new URLSearchParams();
            ['search', 'category'].forEach(function (key) {
                if (currentParams.has(key) && String(currentParams.get(key) || '').trim() !== '') {
                    filteredParams.set(key, String(currentParams.get(key)));
                }
            });
            filteredParams.set('offset', String(nextOffset));
            filteredParams.set('limit', String(limit));
            const url = <?= json_encode($baseUrl . 'shop/loadMore') ?> + '?' + filteredParams.toString();
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json();

            if (!payload || !payload.success) throw new Error('Unable to load more products');

            if (payload.html && String(payload.html).trim() !== '') {
                sentinel.insertAdjacentHTML('beforebegin', payload.html);
            }

            nextOffset = Number(payload.next_offset || nextOffset);
            hasMore = !!payload.has_more;
            grid.dataset.nextOffset = String(nextOffset);
            grid.dataset.hasMore = hasMore ? '1' : '0';

            if (!hasMore || Number(payload.count || 0) === 0) {
                hideInfiniteUi();
            }
        } catch (error) {
            loader.textContent = 'Unable to load more products';
        } finally {
            isLoading = false;
        }
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) loadMore();
        });
    }, { rootMargin: '300px 0px 300px 0px' });

    observer.observe(sentinel);
});
</script>

<?php customer_layout_end(); ?>
