<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        @media (min-width: 992px) {
            .reports-filter-card {
                border-radius: 0 !important;
                padding: 22px !important;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06) !important;
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .reports-summary-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            }

            .reports-table-card {
                border-radius: 0 !important;
                padding: 22px !important;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06) !important;
                border: 1px solid rgba(17, 24, 39, 0.05);
            }
        }
    </style>
</head>
<body>
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px; flex-wrap:wrap;">
            <div>
                <h2 style="margin:0;">Accounting & Reporting</h2>
                <p style="margin:4px 0 0; font-size:12px; color:#888;">Financial overview and daily order reporting for your store.</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>admin/dashboard" style="text-decoration:none; color:#007aff; font-weight:700;">Back to Dashboard</a>
                <a href="<?= BASE_URL ?>order/manage" style="text-decoration:none; color:#007aff; font-weight:700;">Go to Orders</a>
            </div>
        </div>

        <form method="GET" action="<?= BASE_URL ?>order/reports" class="reports-filter-card" style="background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); margin-bottom:18px;">
            <div style="display:grid; gap:12px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
                    <select name="payment_method" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                        <option value="">All Order Types</option>
                        <?php foreach (['cod' => 'Cash on Delivery', 'payhere' => 'PayHere', 'koko' => 'KOKO', 'bank_transfer' => 'Bank Transfer'] as $methodKey => $methodLabel): ?>
                            <option value="<?= $methodKey ?>" <?= (($filters['payment_method'] ?? '') === $methodKey) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($methodLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="payment_status" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                        <option value="">All Payment Statuses</option>
                        <?php foreach (['pending', 'paid', 'failed', 'cancelled', 'verification_failed', 'chargedback'] as $status): ?>
                            <option value="<?= $status ?>" <?= (($filters['payment_status'] ?? '') === $status) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="order_status" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                        <option value="">All Order Statuses</option>
                        <?php foreach (['pending', 'processing', 'completed', 'cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= (($filters['order_status'] ?? '') === $status) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#111; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                        Apply Filters
                    </button>
                    <a href="<?= BASE_URL ?>order/reports" style="text-decoration:none; background:#f3f3f3; color:#222; padding:12px 18px; border-radius:999px; font-weight:700;">Reset</a>
                    <a href="<?= BASE_URL ?>order/export?<?= http_build_query(array_filter($filters, function ($value) { return $value !== ''; })) ?>" style="text-decoration:none; background:#007aff; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700;">
                        Export Orders
                    </a>
                </div>
            </div>
        </form>

        <div class="reports-summary-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:18px;">
            <div style="background:#fff; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#888; margin-bottom:6px;">Gross Order Value</div>
                <div style="font-size:22px; font-weight:800; color:#111;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($finance['gross_total'] ?? 0), 2) ?></div>
            </div>
            <div style="background:#e8fff0; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#1a9b57; margin-bottom:6px;">Paid Revenue</div>
                <div style="font-size:22px; font-weight:800; color:#111;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($finance['paid_total'] ?? 0), 2) ?></div>
            </div>
            <div style="background:#fff8ee; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#9b5d00; margin-bottom:6px;">COD Outstanding</div>
                <div style="font-size:22px; font-weight:800; color:#111;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($finance['cod_outstanding_total'] ?? 0), 2) ?></div>
                <div style="font-size:11px; color:#777; margin-top:4px;"><?= (int) ($finance['cod_outstanding_count'] ?? 0) ?> pending COD orders</div>
            </div>
            <div style="background:#eef5ff; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#2463d0; margin-bottom:6px;">Average Order</div>
                <div style="font-size:22px; font-weight:800; color:#111;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($finance['avg_order_value'] ?? 0), 2) ?></div>
            </div>
        </div>

        <div class="reports-summary-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:18px;">
            <div style="background:#fff4cf; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#8a6b00; margin-bottom:6px;">New Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['new_orders'] ?? 0) ?></div>
            </div>
            <div style="background:#fff8ee; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#9b5d00; margin-bottom:6px;">COD Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['cod_orders'] ?? 0) ?></div>
            </div>
            <div style="background:#f3f0ff; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#5b33d6; margin-bottom:6px;">PayHere Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['payhere_orders'] ?? 0) ?></div>
            </div>
            <div style="background:#fff3dc; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#9b5d00; margin-bottom:6px;">KOKO Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['koko_orders'] ?? 0) ?></div>
            </div>
            <div style="background:#eef7f4; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#26795a; margin-bottom:6px;">Bank Transfer Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['bank_transfer_orders'] ?? 0) ?></div>
            </div>
            <div style="background:#f5f5f5; border-radius:16px; padding:16px; box-shadow:0 4px 18px rgba(0,0,0,0.04);">
                <div style="font-size:11px; color:#666; margin-bottom:6px;">Completed Orders</div>
                <div style="font-size:24px; font-weight:800; color:#111;"><?= (int) ($summary['completed_orders'] ?? 0) ?></div>
            </div>
        </div>

        <div class="reports-table-card" style="background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
                <div>
                    <h3 style="margin:0;">Daily Report</h3>
                    <p style="margin:4px 0 0; font-size:12px; color:#888;">Last 30 daily rows based on your current filters.</p>
                </div>
            </div>

            <?php if (empty($reportRows)): ?>
                <div style="padding:14px; border-radius:14px; background:#fafafa; color:#777;">No reporting data found for the selected filters.</div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table style="width:100%; border-collapse:collapse; min-width:720px;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid #eee;">
                                <th style="padding:10px 8px; font-size:12px; color:#777;">Date</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">Orders</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">Gross</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">Paid</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">COD</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">PayHere</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">KOKO</th>
                                <th style="padding:10px 8px; font-size:12px; color:#777;">Bank Transfer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $row): ?>
                                <tr style="border-bottom:1px solid #f3f3f3;">
                                    <td style="padding:12px 8px; font-size:13px; color:#111; font-weight:700;"><?= htmlspecialchars($row['report_date']) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#222;"><?= (int) ($row['orders_count'] ?? 0) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#222;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['gross_total'] ?? 0), 2) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#1a9b57; font-weight:700;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['paid_total'] ?? 0), 2) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#9b5d00; font-weight:700;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['cod_total'] ?? 0), 2) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#5b33d6; font-weight:700;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['payhere_total'] ?? 0), 2) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#9b5d00; font-weight:700;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['koko_total'] ?? 0), 2) ?></td>
                                    <td style="padding:12px 8px; font-size:13px; color:#26795a; font-weight:700;"><?= htmlspecialchars($settings['currency_symbol'] ?? 'LKR') ?> <?= number_format((float) ($row['bank_transfer_total'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php $current_page = 'orders';
    include 'views/layouts/bottom_nav.php'; ?>
</body>
</html>
