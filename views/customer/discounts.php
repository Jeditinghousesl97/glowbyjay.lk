<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';
require_once 'views/layouts/customer_layout.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$shopName = !empty($settings['shop_name']) ? (string) $settings['shop_name'] : 'STYLE1';
$currency = (string) ($settings['currency_symbol'] ?? 'LKR');
$kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
$discountProducts = array_values(array_filter($products ?? [], static function ($product) {
    return !empty($product['sale_price']) && (float) $product['sale_price'] < (float) $product['price'];
}));
$discountLimit = max(1, (int) ($discounts_limit ?? 20));
$discountHasMore = !empty($discounts_has_more);
$discountCount = max(0, (int) ($discounts_total_products ?? count($discountProducts)));

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

<style>
    .discount-page{
        background:var(--surface);
        color:#1c1b1b;
        padding:34px 0 96px;
    }

    .discount-shell{
        width:min(1600px,calc(100% - 96px));
        margin:0 auto;
    }

    .discount-hero{
        padding:8px 0 28px;
    }

    .discount-head{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:24px;
        margin-bottom:28px;
    }

    .discount-head-left{
        max-width:760px;
    }

    .discount-kicker{
        display:block;
        margin-bottom:8px;
        font-size:11px;
        letter-spacing:.26em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
        font-weight:800;
    }

    .discount-title{
        margin:0;
        font-family:sans-serif;
        font-size:clamp(34px,4vw,54px);
        line-height:1.02;
        letter-spacing:-.04em;
    }

    .discount-copy{
        margin:10px 0 0;
        max-width:64ch;
        color:#6d6665;
        line-height:1.8;
        font-size:15px;
    }

    .discount-count{
        font-size:10px;
        font-weight:800;
        letter-spacing:.2em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
        white-space:nowrap;
    }

    .discount-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:28px 24px;
        align-items:start;
    }

    .discount-card{
        display:grid;
        gap:12px;
        align-content:start;
        height:100%;
    }

    .discount-media{
        position:relative;
        aspect-ratio:1/1;
        overflow:hidden;
        background:#f2efee;
    }

    .discount-media img{
        width:100%;
        height:100%;
        object-fit:cover;
        transition:transform .7s ease;
    }

    .discount-card:hover .discount-media img{
        transform:scale(1.04);
    }

    .discount-badge{
        position:absolute;
        top:16px;
        right:16px;
        background:var(--accent-red, var(--primary));
        color:#fff;
        padding:8px 12px;
        font-size:10px;
        font-weight:800;
        letter-spacing:.16em;
        text-transform:uppercase;
    }

    .discount-shipping{
        position:absolute;
        left:16px;
        bottom:16px;
        background:#1c1b1b;
        color:#fff;
        padding:8px 12px;
        font-size:10px;
        font-weight:800;
        letter-spacing:.16em;
        text-transform:uppercase;
        border-radius:0;
    }

    .discount-body{
        display:grid;
        gap:8px;
        align-content:start;
    }

    .discount-chip{
        font-size:10px;
        letter-spacing:.22em;
        text-transform:uppercase;
        color:#8a8380;
    }

    .discount-name{
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

    .discount-name a{
        color:inherit;
    }

    .discount-price-row{
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
    }

    .discount-sale-price{
        font-size:18px;
        font-weight:800;
        letter-spacing:.1em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
    }

    .discount-old-price{
        font-size:14px;
        font-weight:700;
        color:#8c8785;
        text-decoration:line-through;
    }

    .discount-koko-teaser{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:nowrap;
        white-space:nowrap;
        overflow:hidden;
        min-width:0;
        margin-top:2px;
    }

    .discount-koko-text{
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#6d6665;
    }

    .discount-koko-logo{
        height:16px;
        width:auto;
        flex-shrink:0;
        display:block;
    }

    .discount-desc{
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
    .discount-infinite-loader{
        grid-column:1 / -1;
        text-align:center;
        font-size:12px;
        letter-spacing:.14em;
        text-transform:uppercase;
        color:#8a8380;
        padding:16px 8px 4px;
    }

    .discount-empty{
        grid-column:1 / -1;
        padding:48px 24px;
        text-align:center;
        background:var(--surface);
        box-shadow:0 14px 30px rgba(28,27,27,.06);
    }

    .discount-empty h3{
        margin:0 0 8px;
        font-size:24px;
    }

    .discount-empty p{
        margin:0;
        color:#6d6665;
        line-height:1.8;
    }

    @media (max-width: 1180px){
        .discount-shell{
            width:min(100% - 48px,1600px);
        }

        .discount-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }

    @media (max-width: 760px){
        .discount-page{
            padding:22px 0 72px;
        }

        .discount-shell{
            width:min(100% - 28px,1600px);
        }

        .discount-head{
            flex-direction:column;
            align-items:flex-start;
            gap:10px;
        }

        .discount-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px 14px;
        }

        .discount-badge{
            top:10px;
            right:10px;
            padding:6px 9px;
            font-size:9px;
        }

        .discount-shipping{
            left:10px;
            bottom:10px;
            padding:6px 9px;
            font-size:9px;
        }

        .discount-name{
            font-size:15px;
            min-height:1.25em;
        }

        .discount-sale-price,
        .discount-old-price{
            font-size:12px;
        }

        .discount-desc{
            font-size:12px;
            line-height:1.55;
            min-height:4.96em;
        }
    }
</style>

<main class="discount-page">
    <div class="discount-shell">
        <section class="discount-hero">
            <div class="discount-head">
                <div class="discount-head-left">
                    <span class="discount-kicker"><?= htmlspecialchars($shopName) ?> Sale Picks</span>
                    <h1 class="discount-title">Discounts</h1>
                    <p class="discount-copy">Browse the latest admin-added discounted products in a clean, aligned grid that matches the site’s newer product styling.</p>
                </div>
                <div class="discount-count"><?= (int) $discountCount ?> products</div>
            </div>
        </section>

        <section class="discount-grid" id="discountGrid" aria-label="Discounted products" data-limit="<?= (int) $discountLimit ?>" data-next-offset="<?= count($discountProducts) ?>" data-has-more="<?= $discountHasMore ? '1' : '0' ?>">
            <?php if (!empty($discountProducts)): ?>
                <?php foreach ($discountProducts as $product): ?>
                    <?php
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
                        <a class="discount-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
                            <?= ImageHelper::renderResponsivePicture(
                                $product['main_image'] ?? '',
                                $productImage,
                                [
                                    'alt' => $product['title'] ?? 'Discount product',
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'fetchpriority' => 'low'
                                ],
                                'product_card'
                            ) ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?= $discount ?>%</span>
                            <?php endif; ?>
                            <?php if (!empty($product['free_shipping'])): ?>
                                <span class="discount-shipping">Free Shipping</span>
                            <?php endif; ?>
                        </a>

                        <div class="discount-body">
                            <span class="discount-chip"><?= htmlspecialchars($product['parent_category_name'] ?? $product['category_name'] ?? 'Discounted Item') ?></span>
                            <h2 class="discount-name">
                                <a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>"><?= htmlspecialchars($product['title'] ?? 'Product') ?></a>
                            </h2>

                            <div class="discount-price-row">
                                <span class="discount-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($salePrice, 0) ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="discount-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="discount-empty">
                    <h3>No discounted products yet</h3>
                    <p>When the admin marks products as discounted, they will appear here automatically.</p>
                </div>
            <?php endif; ?>
            <div id="discountInfiniteLoader" class="discount-infinite-loader" style="<?= $discountHasMore ? '' : 'display:none;' ?>">Loading more products...</div>
            <div id="discountInfiniteSentinel" style="<?= $discountHasMore ? '' : 'display:none;' ?>" aria-hidden="true"></div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('discountGrid');
    const loader = document.getElementById('discountInfiniteLoader');
    const sentinel = document.getElementById('discountInfiniteSentinel');
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
            const url = <?= json_encode($baseUrl . 'discounts/loadMore') ?> + '?offset=' + encodeURIComponent(String(nextOffset)) + '&limit=' + encodeURIComponent(String(limit));
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
