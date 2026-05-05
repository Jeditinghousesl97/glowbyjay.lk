<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Stock Management') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        .stock-summary-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:24px; }
        .stock-summary-card { background:#fff; border-radius:16px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); }
        .stock-summary-label { font-size:12px; color:#777; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.04em; font-weight:700; }
        .stock-summary-value { font-size:30px; font-weight:800; color:#111; }
        .stock-filter-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
        .stock-filter-chip { padding:10px 14px; border-radius:999px; background:#fff; border:1px solid #ececec; color:#555; text-decoration:none; font-size:12px; font-weight:700; }
        .stock-filter-chip.active { background:#111; color:#fff; border-color:#111; }
        .stock-list { display:grid; gap:12px; }
        .stock-item { background:#fff; border-radius:18px; padding:16px; box-shadow:0 4px 20px rgba(0,0,0,0.04); }
        .stock-item-top { display:flex; justify-content:space-between; gap:12px; margin-bottom:10px; }
        .stock-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; }
        .stock-badge.in_stock { background:#ecf8ef; color:#1d7a40; }
        .stock-badge.low_stock { background:#fff6e7; color:#9a6a11; }
        .stock-badge.out_of_stock { background:#fff1f0; color:#d83b31; }
        .stock-meta { font-size:12px; color:#777; line-height:1.6; }
        .stock-actions { display:flex; gap:10px; margin-top:12px; }
        .stock-btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 12px; border-radius:10px; text-decoration:none; font-size:12px; font-weight:700; }
        .stock-btn.primary { background:#007aff; color:#fff; }
        .stock-btn.secondary { background:#f3f3f3; color:#333; }

        @media (min-width: 992px) {
            .stock-summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .stock-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .stock-item {
                border-radius: 22px;
                padding: 20px;
                box-shadow: 0 16px 34px rgba(17, 24, 39, 0.06);
                border: 1px solid rgba(17, 24, 39, 0.05);
            }
        }
    </style>
</head>
<body>
<?php include 'views/admin/partials/loader.php'; ?>
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Stock Management</h1>
            <p class="shop-subtitle">Track simple products, variant combinations, low stock, and out-of-stock items.</p>
        </div>
        <div class="stock-actions" style="margin-top:0;">
            <a href="<?= BASE_URL ?>stock/report" class="stock-btn primary">Open Stock Report</a>
            <a href="<?= BASE_URL ?>admin/dashboard" class="stock-btn secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="stock-summary-grid">
        <div class="stock-summary-card">
            <div class="stock-summary-label">Tracked Products</div>
            <div class="stock-summary-value"><?= (int) ($summary['tracked_products'] ?? 0) ?></div>
        </div>
        <div class="stock-summary-card">
            <div class="stock-summary-label">Variant Products</div>
            <div class="stock-summary-value"><?= (int) ($summary['variant_products'] ?? 0) ?></div>
        </div>
        <div class="stock-summary-card">
            <div class="stock-summary-label">Low Stock</div>
            <div class="stock-summary-value"><?= (int) ($summary['low_stock'] ?? 0) ?></div>
        </div>
        <div class="stock-summary-card">
            <div class="stock-summary-label">Out of Stock</div>
            <div class="stock-summary-value"><?= (int) ($summary['out_of_stock'] ?? 0) ?></div>
        </div>
    </div>

    <div class="stock-filter-row">
        <?php foreach (['' => 'All', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock', 'variant' => 'Variant Products'] as $key => $label): ?>
            <a href="<?= BASE_URL ?>stock/index<?= $key !== '' ? '?filter=' . urlencode($key) : '' ?>"
               class="stock-filter-chip <?= ($active_filter ?? '') === $key ? 'active' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="stock-list">
        <?php foreach (($products ?? []) as $product): ?>
            <?php $snapshot = $product['stock_snapshot'] ?? []; ?>
            <div class="stock-item">
                <div class="stock-item-top">
                    <div>
                        <div style="font-size:16px; font-weight:800; color:#111;"><?= htmlspecialchars($product['title'] ?? 'Product') ?></div>
                        <div style="font-size:12px; color:#777; margin-top:4px;"><?= htmlspecialchars($product['sku'] ?? 'No SKU') ?></div>
                    </div>
                    <span class="stock-badge <?= htmlspecialchars($snapshot['status'] ?? 'in_stock') ?>">
                        <?= htmlspecialchars(str_replace('_', ' ', $snapshot['status'] ?? 'in_stock')) ?>
                    </span>
                </div>
                <div class="stock-meta">
                    <div>Mode: <strong><?= htmlspecialchars(str_replace('_', ' ', $snapshot['stock_mode'] ?? 'always_in_stock')) ?></strong></div>
                    <div>Available Qty: <strong><?= $snapshot['available_qty'] === null ? 'Unlimited / manual' : (int) $snapshot['available_qty'] ?></strong></div>
                    <div>Variant Combinations: <strong><?= !empty($snapshot['variant_rows']) ? count($snapshot['variant_rows']) : 0 ?></strong></div>
                </div>
                <div class="stock-actions">
                    <a href="<?= BASE_URL ?>product/edit/<?= (int) $product['id'] ?>" class="stock-btn primary">Manage Stock</a>
                    <a href="<?= BASE_URL ?>shop/product/<?= (int) $product['id'] ?>" class="stock-btn secondary" target="_blank">View Product</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <div class="stock-item">
                <div style="font-size:13px; color:#777;">No products matched this stock filter.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
