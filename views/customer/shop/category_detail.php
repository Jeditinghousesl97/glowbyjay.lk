<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';
require_once ROOT_PATH . 'helpers/KokoPricingHelper.php';
require_once 'views/layouts/customer_layout.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$shopName = !empty($settings['shop_name']) ? (string) $settings['shop_name'] : 'STYLE1';
$currency = (string) ($settings['currency_symbol'] ?? 'LKR');
$kokoLogoUrl = BASE_URL . 'assets/icons/payment-gateways/koko-home.png?v=' . (@filemtime(ROOT_PATH . 'assets/icons/payment-gateways/koko-home.png') ?: time());
$categoryName = (string) ($category['name'] ?? 'Category');
$categoryCount = (int) ($category_count ?? (is_array($products ?? null) ? count($products) : 0));
$categoryImageUrl = !empty($category_image)
    ? (string) $category_image
    : ImageHelper::uploadUrl(
        $category['image'] ?? '',
        'https://via.placeholder.com/1200x1500?text=' . urlencode($categoryName ?: 'Category')
    );
$categoryCopy = !empty($subCategories)
    ? 'Browse products in ' . $categoryName . ' and its subcategories in the site\'s clean discount-style grid.'
    : 'Browse all products in ' . $categoryName . ' in the same clean, aligned product layout used across the site.';

customer_layout_start();
?>

<style>
    .category-page{
        background:var(--surface);
        color:#1c1b1b;
        padding:32px 0 92px;
    }

    .category-shell{
        width:min(1600px,calc(100% - 96px));
        margin:0 auto;
    }

    .category-hero{
        display:grid;
        grid-template-columns:minmax(0,1fr) minmax(280px,420px);
        gap:28px;
        align-items:center;
        padding:8px 0 24px;
    }

    .category-head{
        display:flex;
        flex-direction:column;
        justify-content:center;
        gap:14px;
        min-height:0;
        padding:6px 0;
    }

    .category-head-left{
        max-width:680px;
    }

    .category-kicker{
        display:block;
        margin-bottom:8px;
        font-size:11px;
        letter-spacing:.26em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
        font-weight:800;
    }

    .category-title{
        margin:0;
        font-family:"Noto Serif",serif;
        font-size:clamp(34px,4vw,56px);
        line-height:1.02;
        letter-spacing:-.04em;
    }

    .category-copy{
        margin:8px 0 0;
        max-width:54ch;
        color:#6d6665;
        line-height:1.72;
        font-size:15px;
    }

    .category-meta-row{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-top:16px;
    }

    .category-meta-pill{
        display:inline-flex;
        align-items:center;
        min-height:38px;
        padding:0 14px;
        border:1px solid rgba(28,27,27,.12);
        background:var(--surface);
        color:#1c1b1b;
        font-size:11px;
        font-weight:800;
        letter-spacing:.18em;
        text-transform:uppercase;
        white-space:nowrap;
    }

    .category-subchips{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-top:6px;
    }

    .category-subchip{
        display:inline-flex;
        align-items:center;
        min-height:38px;
        padding:0 14px;
        border:1px solid rgba(28,27,27,.12);
        background:var(--surface);
        color:#1c1b1b;
        font-size:11px;
        font-weight:800;
        letter-spacing:.18em;
        text-transform:uppercase;
        white-space:nowrap;
    }

    .category-visual{
        position:relative;
        overflow:hidden;
        width:min(100%,420px);
        justify-self:end;
        aspect-ratio:4/5;
        background:#f2efee;
        border:1px solid rgba(28,27,27,.08);
        box-shadow:0 14px 30px rgba(28,27,27,.06);
    }

    .category-visual img{
        width:100%;
        height:100%;
        object-fit:cover;
        transition:transform .7s ease;
    }

    .category-visual:hover img{
        transform:scale(1.03);
    }

    .category-overlay{
        position:absolute;
        left:0;
        right:0;
        bottom:0;
        padding:14px 16px;
        background:linear-gradient(180deg,rgba(28,27,27,0) 0%, rgba(28,27,27,.88) 100%);
        color:#fff;
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-end;
    }

    .category-overlay h2{
        margin:0;
        font-family:"Noto Serif",serif;
        font-size:18px;
        line-height:1.1;
        letter-spacing:-.02em;
    }

    .category-overlay span{
        font-size:10px;
        font-weight:800;
        letter-spacing:.2em;
        text-transform:uppercase;
        color:rgba(255,255,255,.74);
        white-space:nowrap;
    }

    .category-grid{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:28px 24px;
        align-items:start;
    }

    .category-card{
        display:grid;
        gap:12px;
        align-content:start;
        height:100%;
    }

    .category-media{
        position:relative;
        aspect-ratio:4/5;
        overflow:hidden;
        background:#f2efee;
    }

    .category-media img{
        width:100%;
        height:100%;
        object-fit:cover;
        transition:transform .7s ease;
    }

    .category-card:hover .category-media img{
        transform:scale(1.04);
    }

    .category-badge{
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

    .category-shipping{
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

    .category-body{
        display:grid;
        gap:8px;
        align-content:start;
    }

    .category-chip{
        font-size:10px;
        letter-spacing:.22em;
        text-transform:uppercase;
        color:#8a8380;
    }

    .category-name{
        margin:0;
        font-family:"Noto Serif",serif;
        font-size:18px;
        line-height:1.25;
        letter-spacing:-.02em;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        min-height:1.25em;
    }

    .category-name a{
        color:inherit;
    }

    .category-price-row{
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
    }

    .category-sale-price{
        font-size:12px;
        font-weight:800;
        letter-spacing:.1em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
    }

    .category-old-price{
        font-size:12px;
        font-weight:700;
        color:#8c8785;
        text-decoration:line-through;
    }

    .category-koko-teaser{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:nowrap;
        white-space:nowrap;
        overflow:hidden;
        min-width:0;
        margin-top:2px;
    }

    .category-koko-text{
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#6d6665;
    }

    .category-koko-logo{
        height:16px;
        width:auto;
        flex-shrink:0;
        display:block;
    }

    .category-desc{
        margin:0;
        color:#6d6665;
        font-size:13px;
        line-height:1.7;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden;
        min-height:2.85em;
    }

    .category-empty{
        grid-column:1 / -1;
        padding:48px 24px;
        text-align:center;
        background:var(--surface);
        box-shadow:0 14px 30px rgba(28,27,27,.06);
    }

    .category-empty h3{
        margin:0 0 8px;
        font-size:24px;
    }

    .category-empty p{
        margin:0;
        color:#6d6665;
        line-height:1.8;
    }

    @media (max-width: 1180px){
        .category-shell{
            width:min(100% - 48px,1600px);
        }

        .category-hero{
            grid-template-columns:1fr;
        }

        .category-visual{
            justify-self:start;
        }

        .category-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
    }

    @media (max-width: 760px){
        .category-page{
            padding:22px 0 72px;
        }

        .category-shell{
            width:min(100% - 28px,1600px);
        }

        .category-hero{
            gap:16px;
            padding-bottom:18px;
        }

        .category-head{
            padding:0;
        }

        .category-copy{
            font-size:14px;
            line-height:1.65;
        }

        .category-meta-row{
            margin-top:12px;
        }

        .category-visual{
            width:100%;
            aspect-ratio:16/10;
        }

        .category-overlay{
            padding:12px 14px;
        }

        .category-overlay h2{
            font-size:16px;
        }

        .category-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px 14px;
        }

        .category-badge{
            top:10px;
            right:10px;
            padding:6px 9px;
            font-size:9px;
        }

        .category-shipping{
            left:10px;
            bottom:10px;
            padding:6px 9px;
            font-size:9px;
        }

        .category-name{
            font-size:15px;
            min-height:1.25em;
        }

        .category-sale-price,
        .category-old-price{
            font-size:11px;
        }

        .category-desc{
            font-size:12px;
            line-height:1.55;
            min-height:2.55em;
        }
    }
</style>

<main class="category-page">
    <div class="category-shell">
        <section class="category-hero">
            <div class="category-head">
                <div class="category-head-left">
                    <span class="category-kicker"><?= htmlspecialchars($shopName) ?> Collections</span>
                    <h1 class="category-title"><?= htmlspecialchars($categoryName) ?></h1>
                    <p class="category-copy"><?= htmlspecialchars($categoryCopy) ?></p>
                    <div class="category-meta-row">
                        <span class="category-meta-pill"><?= (int) $categoryCount ?> Products</span>
                        <span class="category-meta-pill"><?= htmlspecialchars($categoryName) ?></span>
                    </div>
                    <?php if (!empty($subCategories)): ?>
                        <div class="category-subchips" aria-label="Subcategories">
                            <?php foreach ($subCategories as $subCategory): ?>
                                <a class="category-subchip" href="<?= htmlspecialchars($baseUrl . 'shop/category/' . (int) ($subCategory['id'] ?? 0)) ?>">
                                    <?= htmlspecialchars((string) ($subCategory['name'] ?? 'Subcategory')) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <a class="category-visual" href="<?= htmlspecialchars($baseUrl . 'shop/categories') ?>">
                <img src="<?= htmlspecialchars($categoryImageUrl) ?>" alt="<?= htmlspecialchars($categoryName) ?>">
                <div class="category-overlay">
                    <h2><?= htmlspecialchars($categoryName) ?></h2>
                    <span>Collection Hero</span>
                </div>
            </a>
        </section>

        <section class="category-grid" aria-label="Category products">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $regularPrice = (float) ($product['price'] ?? 0);
                    $salePrice = (float) ($product['sale_price'] ?? $regularPrice);
                    $discount = ($regularPrice > 0 && $salePrice < $regularPrice)
                        ? (int) round((1 - ($salePrice / $regularPrice)) * 100)
                        : 0;
                    $productImage = ImageHelper::uploadUrl(
                        $product['main_image'] ?? '',
                        'https://via.placeholder.com/720x900?text=' . urlencode($product['title'] ?? 'Product')
                    );
                    $description = trim((string) ($product['short_description'] ?? $product['description'] ?? ''));
                    ?>
                    <article class="category-card">
                        <a class="category-media" href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>">
                            <?= ImageHelper::renderResponsivePicture(
                                $product['main_image'] ?? '',
                                $productImage,
                                [
                                    'alt' => $product['title'] ?? 'Category product',
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'fetchpriority' => 'low'
                                ],
                                'product_card'
                            ) ?>
                            <?php if ($discount > 0): ?>
                                <span class="category-badge">-<?= $discount ?>%</span>
                            <?php endif; ?>
                            <?php if (!empty($product['free_shipping'])): ?>
                                <span class="category-shipping">Free Shipping</span>
                            <?php endif; ?>
                        </a>

                        <div class="category-body">
                            <span class="category-chip">Category Product</span>
                            <h2 class="category-name">
                                <a href="<?= htmlspecialchars($baseUrl . 'shop/product/' . $product['id']) ?>"><?= htmlspecialchars($product['title'] ?? 'Product') ?></a>
                            </h2>

                            <div class="category-price-row">
                                <span class="category-sale-price"><?= htmlspecialchars($currency) ?> <?= number_format($salePrice, 0) ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="category-old-price"><?= htmlspecialchars($currency) ?> <?= number_format($regularPrice, 0) ?></span>
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
                                    <div class="category-koko-teaser" aria-label="KOKO installment plan">
                                        <span class="category-koko-text">or 3 x <?= htmlspecialchars($currency) ?> <?= number_format((float) $kokoTeaser['installment_amount'], 0) ?></span>
                                        <img src="<?= htmlspecialchars($kokoLogoUrl) ?>" alt="KOKO" class="category-koko-logo">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($description !== ''): ?>
                                <p class="category-desc"><?= htmlspecialchars($description) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="category-empty">
                    <h3>No products in this category yet</h3>
                    <p>When products are assigned to this category, they will appear here automatically.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php customer_layout_end(); ?>
