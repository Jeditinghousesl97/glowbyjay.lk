<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Stock Report') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        body { background:#f6f8fb; }
        .report-shell { max-width:1320px; margin:0 auto; }
        .report-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .report-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .report-btn { display:inline-flex; align-items:center; justify-content:center; padding:11px 16px; border-radius:12px; text-decoration:none; font-size:13px; font-weight:800; border:none; cursor:pointer; }
        .report-btn.primary { background:#111827; color:#fff; }
        .report-btn.secondary { background:#fff; color:#374151; border:1px solid #dbe2ea; }
        .report-btn.export { background:#177245; color:#fff; }
        .report-card,
        .report-filter-card,
        .product-card,
        .tip-card { background:#fff; border-radius:22px; box-shadow:0 10px 34px rgba(15, 23, 42, 0.06); }
        .report-filter-card { padding:18px; margin-bottom:20px; }
        .filter-grid { display:grid; grid-template-columns:2fr repeat(4, minmax(140px, 1fr)) repeat(2, minmax(150px, 1fr)); gap:12px; }
        .report-input,
        .report-select { width:100%; padding:12px 14px; border:1px solid #dbe2ea; border-radius:14px; font-size:13px; box-sizing:border-box; background:#fff; }
        .filter-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .summary-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:14px; margin-bottom:20px; }
        .report-card { padding:18px; }
        .summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280; margin-bottom:10px; }
        .summary-value { font-size:28px; font-weight:900; color:#111827; }
        .summary-sub { margin-top:6px; font-size:12px; color:#6b7280; line-height:1.5; }
        .helper-grid { display:grid; grid-template-columns:1.3fr 1fr; gap:16px; margin-bottom:20px; }
        .tip-card { padding:18px 20px; }
        .tip-title { font-size:18px; font-weight:900; color:#111827; margin-bottom:8px; }
        .tip-copy { font-size:13px; color:#4b5563; line-height:1.7; }
        .pill-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
        .info-pill,
        .status-pill { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; font-size:11px; font-weight:900; padding:7px 10px; text-transform:uppercase; letter-spacing:0.05em; }
        .info-pill { background:#eef2ff; color:#334155; }
        .status-pill.in_stock { background:#e9f8ef; color:#157347; }
        .status-pill.low_stock { background:#fff4db; color:#a15c00; }
        .status-pill.out_of_stock { background:#ffe9e7; color:#c93c2c; }
        .products-list { display:grid; gap:16px; }
        .product-card { overflow:hidden; }
        .product-top { padding:20px 20px 16px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; border-bottom:1px solid #edf1f5; }
        .product-title { font-size:22px; font-weight:900; color:#111827; margin-bottom:6px; }
        .product-sub { font-size:13px; color:#6b7280; line-height:1.6; }
        .product-metrics { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; padding:16px 20px; background:#fbfcfe; border-bottom:1px solid #edf1f5; }
        .metric-box { border:1px solid #e8edf3; border-radius:16px; padding:12px 14px; background:#fff; }
        .metric-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px; }
        .metric-value { font-size:18px; font-weight:900; color:#111827; }
        .metric-sub { margin-top:4px; font-size:12px; color:#6b7280; }
        .variant-summary { padding:16px 20px; border-bottom:1px solid #edf1f5; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .variant-chip { display:inline-flex; align-items:center; gap:6px; background:#f3f7fb; color:#334155; border-radius:999px; padding:9px 12px; font-size:12px; font-weight:800; }
        .variant-section { padding:16px 20px 20px; }
        .section-title { font-size:15px; font-weight:900; color:#111827; margin-bottom:12px; }
        .variant-table-wrap { overflow:auto; border:1px solid #e8edf3; border-radius:18px; }
        .variant-table { width:100%; min-width:880px; border-collapse:collapse; background:#fff; }
        .variant-table th,
        .variant-table td { padding:13px 12px; border-bottom:1px solid #edf1f5; text-align:left; vertical-align:top; font-size:13px; }
        .variant-table th { font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280; background:#f8fafc; }
        .variant-label { font-size:14px; font-weight:900; color:#166534; }
        .variant-sub { margin-top:4px; font-size:12px; color:#6b7280; }
        .money { font-weight:800; color:#111827; }
        .muted { color:#6b7280; }
        .product-actions { padding:0 20px 20px; display:flex; gap:10px; flex-wrap:wrap; }
        .empty-state { padding:28px; border:1px dashed #dbe2ea; border-radius:18px; background:#fff; color:#6b7280; font-size:14px; text-align:center; }
        @media (max-width: 960px) {
            .filter-grid,
            .helper-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php include 'views/admin/partials/loader.php'; ?>
<?php $currency = htmlspecialchars($settings['currency_symbol'] ?? 'LKR'); ?>
<div class="container report-shell">
    <div class="report-header">
        <div>
            <h1 class="page-title" style="margin-bottom:8px;">Stock Report</h1>
            <p class="shop-subtitle" style="max-width:860px;">This page is rebuilt to answer two clear questions: which products need attention now, and which exact variant combinations are causing that issue.</p>
        </div>
        <div class="report-actions">
            <a href="<?= BASE_URL ?>admin/dashboard" class="report-btn secondary">Back to Dashboard</a>
            <a href="<?= BASE_URL ?>stock/index" class="report-btn secondary">Stock Management</a>
            <a href="<?= BASE_URL ?>stock/exportReport?<?= htmlspecialchars(http_build_query(array_filter($filters ?? [], function ($value) { return $value !== ''; }))) ?>" class="report-btn export">Export CSV</a>
        </div>
    </div>

    <form method="GET" action="<?= BASE_URL ?>stock/report" class="report-filter-card">
        <div class="filter-grid">
            <input type="text" name="search" class="report-input" placeholder="Search by product, SKU, category, or variant combination" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            <select name="stock_state" class="report-select">
                <option value="">All Stock States</option>
                <option value="in_stock" <?= ($filters['stock_state'] ?? '') === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                <option value="low_stock" <?= ($filters['stock_state'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                <option value="out_of_stock" <?= ($filters['stock_state'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
            <select name="product_type" class="report-select">
                <option value="">All Product Types</option>
                <option value="simple" <?= ($filters['product_type'] ?? '') === 'simple' ? 'selected' : '' ?>>Simple Products</option>
                <option value="variant" <?= ($filters['product_type'] ?? '') === 'variant' ? 'selected' : '' ?>>Variant Products</option>
            </select>
            <select name="payment_status" class="report-select">
                <option value="">All Payment States</option>
                <option value="paid" <?= ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="pending" <?= ($filters['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= ($filters['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
            <select name="order_status" class="report-select">
                <option value="">All Order States</option>
                <option value="pending" <?= ($filters['order_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= ($filters['order_status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="completed" <?= ($filters['order_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($filters['order_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <input type="date" name="date_from" class="report-input" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            <input type="date" name="date_to" class="report-input" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="report-btn primary">Apply Filters</button>
            <a href="<?= BASE_URL ?>stock/report" class="report-btn secondary">Reset</a>
        </div>
    </form>

    <div class="summary-grid">
        <div class="report-card">
            <div class="summary-label">Products In Report</div>
            <div class="summary-value"><?= (int) ($summary['total_products'] ?? 0) ?></div>
            <div class="summary-sub"><?= (int) ($summary['variant_products'] ?? 0) ?> variant products and <?= (int) ($summary['simple_products'] ?? 0) ?> simple products</div>
        </div>
        <div class="report-card">
            <div class="summary-label">Need Attention</div>
            <div class="summary-value"><?= (int) ($summary['attention_products'] ?? 0) ?></div>
            <div class="summary-sub"><?= (int) ($summary['low_stock'] ?? 0) ?> low stock and <?= (int) ($summary['out_of_stock'] ?? 0) ?> out of stock products</div>
        </div>
        <div class="report-card">
            <div class="summary-label">Tracked Variants</div>
            <div class="summary-value"><?= (int) ($summary['tracked_variants'] ?? 0) ?></div>
            <div class="summary-sub"><?= (int) ($summary['low_stock_variants'] ?? 0) ?> low and <?= (int) ($summary['out_of_stock_variants'] ?? 0) ?> out of stock variant combinations</div>
        </div>
        <div class="report-card">
            <div class="summary-label">Units On Hand</div>
            <div class="summary-value"><?= (int) ($summary['units_on_hand'] ?? 0) ?></div>
            <div class="summary-sub"><?= $currency ?> <?= number_format((float) ($summary['inventory_value'] ?? 0), 2) ?> estimated tracked stock value</div>
        </div>
        <div class="report-card">
            <div class="summary-label">Units Sold</div>
            <div class="summary-value"><?= (int) ($summary['total_units_sold'] ?? 0) ?></div>
            <div class="summary-sub"><?= $currency ?> <?= number_format((float) ($summary['total_sales_revenue'] ?? 0), 2) ?> revenue in the selected order period</div>
        </div>
        <div class="report-card">
            <div class="summary-label">Products With Sales</div>
            <div class="summary-value"><?= (int) ($summary['products_with_sales'] ?? 0) ?></div>
            <div class="summary-sub"><?= (int) ($summary['zero_sales_products'] ?? 0) ?> products had no recorded sales in this filtered view</div>
        </div>
    </div>

    <div class="helper-grid">
        <div class="tip-card">
            <div class="tip-title">How To Read This Report</div>
            <div class="tip-copy">Each product card shows the overall product health first. If the product has variations, the exact variant combinations are shown in a separate table so you can immediately see which size, color, or option is low or out of stock.</div>
            <div class="pill-row">
                <span class="status-pill in_stock">In Stock</span>
                <span class="status-pill low_stock">Low Stock</span>
                <span class="status-pill out_of_stock">Out Of Stock</span>
                <span class="info-pill">Always In Stock = unlimited selling</span>
                <span class="info-pill">Track Stock = qty is reduced by orders</span>
            </div>
        </div>
        <div class="tip-card">
            <div class="tip-title">Quick Focus</div>
            <div class="tip-copy">If you want only problem items, use the <strong>Low Stock</strong> or <strong>Out of Stock</strong> filter. For variable products, the product may still be sellable while one or more combinations underneath already need attention.</div>
            <div class="pill-row">
                <span class="info-pill"><?= (int) ($summary['attention_products'] ?? 0) ?> products need review</span>
                <span class="info-pill"><?= (int) ($summary['low_stock_variants'] ?? 0) ?> low-stock variants</span>
                <span class="info-pill"><?= (int) ($summary['out_of_stock_variants'] ?? 0) ?> out-of-stock variants</span>
            </div>
        </div>
    </div>

    <div class="products-list">
        <?php foreach (($rows ?? []) as $row): ?>
            <?php
                $isVariantProduct = !empty($row['has_variant_stock']);
                $variantSummary = $row['variant_summary'] ?? ['total' => 0, 'tracked' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'in_stock' => 0];
                $rowStatus = (string) ($row['status'] ?? 'in_stock');
                $availableQtyText = $row['available_qty'] === null ? 'Unlimited / not quantity-based' : (int) $row['available_qty'];
                $effectivePriceText = $currency . ' ' . number_format((float) ($row['effective_price'] ?? 0), 2);
            ?>
            <div class="product-card">
                <div class="product-top">
                    <div>
                        <div class="product-title"><?= htmlspecialchars($row['title'] ?? 'Product') ?></div>
                        <div class="product-sub">
                            <?= htmlspecialchars($row['sku'] ?: 'No SKU') ?>
                            <?php if (!empty($row['category_name'])): ?> | <?= htmlspecialchars($row['category_name']) ?><?php endif; ?>
                            | <?= $isVariantProduct ? 'Variant Product' : 'Simple Product' ?>
                        </div>
                    </div>
                    <div class="pill-row" style="margin-top:0;">
                        <span class="status-pill <?= htmlspecialchars($rowStatus) ?>"><?= htmlspecialchars(str_replace('_', ' ', $rowStatus)) ?></span>
                        <?php if (!empty($row['is_active'])): ?>
                            <span class="info-pill">Active</span>
                        <?php else: ?>
                            <span class="info-pill" style="background:#fef3c7; color:#92400e;">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-metrics">
                    <div class="metric-box">
                        <div class="metric-label">Available Qty</div>
                        <div class="metric-value"><?= htmlspecialchars((string) $availableQtyText) ?></div>
                        <div class="metric-sub"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($row['stock_mode'] ?? 'always_in_stock')))) ?></div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Selling Price</div>
                        <div class="metric-value"><?= htmlspecialchars($effectivePriceText) ?></div>
                        <div class="metric-sub">Current effective product price</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Inventory Value</div>
                        <div class="metric-value"><?= $row['inventory_value'] === null ? 'Not fixed' : htmlspecialchars($currency . ' ' . number_format((float) $row['inventory_value'], 2)) ?></div>
                        <div class="metric-sub">Based on tracked available quantity</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">Sales</div>
                        <div class="metric-value"><?= (int) ($row['units_sold'] ?? 0) ?> units</div>
                        <div class="metric-sub"><?= (int) ($row['orders_count'] ?? 0) ?> orders<?php if (!empty($row['last_ordered_at'])): ?> | Last: <?= htmlspecialchars(date('Y-m-d H:i', strtotime((string) $row['last_ordered_at']))) ?><?php endif; ?></div>
                    </div>
                </div>

                <?php if ($isVariantProduct): ?>
                    <div class="variant-summary">
                        <span class="variant-chip"><?= (int) ($variantSummary['total'] ?? 0) ?> combinations</span>
                        <span class="variant-chip"><?= (int) ($variantSummary['tracked'] ?? 0) ?> tracked</span>
                        <span class="variant-chip"><?= (int) ($variantSummary['in_stock'] ?? 0) ?> in stock</span>
                        <span class="variant-chip"><?= (int) ($variantSummary['low_stock'] ?? 0) ?> low stock</span>
                        <span class="variant-chip"><?= (int) ($variantSummary['out_of_stock'] ?? 0) ?> out of stock</span>
                    </div>
                    <div class="variant-section">
                        <div class="section-title">Variant Breakdown</div>
                        <div class="variant-table-wrap">
                            <table class="variant-table">
                                <thead>
                                    <tr>
                                        <th>Combination</th>
                                        <th>Status</th>
                                        <th>Mode</th>
                                        <th>Qty</th>
                                        <th>Low Stock</th>
                                        <th>Price</th>
                                        <th>Sale Price</th>
                                        <th>Weight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($row['variant_rows'] ?? []) as $variantRow): ?>
                                        <tr>
                                            <td>
                                                <div class="variant-label"><?= htmlspecialchars($variantRow['combination_label'] ?: $variantRow['combination_key']) ?></div>
                                                <div class="variant-sub"><?= htmlspecialchars($variantRow['sku'] ?: 'No variant SKU') ?></div>
                                            </td>
                                            <td><span class="status-pill <?= htmlspecialchars($variantRow['status'] ?? 'in_stock') ?>"><?= htmlspecialchars(str_replace('_', ' ', (string) ($variantRow['status'] ?? 'in_stock'))) ?></span></td>
                                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($variantRow['stock_mode'] ?? 'always_in_stock')))) ?></td>
                                            <td><?= $variantRow['available_qty'] === null ? 'Unlimited' : (int) ($variantRow['available_qty'] ?? 0) ?></td>
                                            <td><?= ((string) ($variantRow['stock_mode'] ?? '')) === 'track_stock' ? (int) ($variantRow['low_stock_threshold'] ?? 0) : '-' ?></td>
                                            <td class="money"><?= $variantRow['variant_price'] !== null ? htmlspecialchars($currency . ' ' . number_format((float) $variantRow['variant_price'], 2)) : '<span class="muted">Use product price</span>' ?></td>
                                            <td class="money"><?= $variantRow['variant_sale_price'] !== null ? htmlspecialchars($currency . ' ' . number_format((float) $variantRow['variant_sale_price'], 2)) : '<span class="muted">-</span>' ?></td>
                                            <td><?= (int) ($variantRow['variant_weight_grams'] ?? 0) ?> g</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="product-actions">
                    <a href="<?= BASE_URL ?>product/edit/<?= (int) $row['id'] ?>" class="report-btn primary">Manage Product</a>
                    <a href="<?= BASE_URL ?>shop/product/<?= (int) $row['id'] ?>" class="report-btn secondary" target="_blank">View Product</a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
            <div class="empty-state">No products matched the current stock report filters.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
