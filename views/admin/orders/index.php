<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        @media (min-width: 992px) {
            .orders-toolbar {
                align-items: center !important;
                margin-bottom: 24px !important;
            }

            .orders-filter-card {
                border-radius: 0 !important;
                padding: 22px !important;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06) !important;
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .orders-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px !important;
            }

            .order-card {
                border-radius: 0 !important;
                padding: 20px !important;
                box-shadow: 0 16px 34px rgba(17, 24, 39, 0.06) !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="page-header orders-toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px; flex-wrap:wrap;">
            <div>
                <h2 style="margin:0;">Orders</h2>
                <p style="margin:4px 0 0; font-size:12px; color:#888;">Track new orders, filter them quickly, and export exactly what you need.</p>
            </div>
            <a href="<?= BASE_URL ?>admin/dashboard" style="text-decoration:none; color:#007aff; font-weight:700;">Back to Dashboard</a>
        </div>

        <form method="GET" action="<?= BASE_URL ?>order/manage" class="orders-filter-card" style="background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); margin-bottom:18px;">
            <div style="display:grid; gap:12px;">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search by order no, customer, email, or phone" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px;">
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

                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#555;">
                    <input type="checkbox" name="only_new" value="1" <?= !empty($filters['only_new']) ? 'checked' : '' ?>>
                    Show only new orders
                </label>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#111; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                        Apply Filters
                    </button>
                    <a href="<?= BASE_URL ?>order/manage" style="text-decoration:none; background:#f3f3f3; color:#222; padding:12px 18px; border-radius:999px; font-weight:700;">Reset</a>
                    <a href="<?= BASE_URL ?>order/export?<?= http_build_query(array_filter($filters, function ($value) { return $value !== ''; })) ?>" style="text-decoration:none; background:#007aff; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700;">
                        Export Excel
                    </a>
                </div>
            </div>
        </form>

        <?php if (empty($orders)): ?>
            <div style="padding:24px; background:#fff; border-radius:16px; color:#777;">No orders found for the selected filters.</div>
        <?php else: ?>
            <div class="orders-grid" style="display:grid; gap:14px;">
                <?php foreach ($orders as $order): ?>
                    <?php $isNew = empty($order['admin_seen_at']); ?>
                    <a href="<?= BASE_URL ?>order/details/<?= urlencode($order['order_number']) ?>" class="order-card" style="display:block; text-decoration:none; color:inherit; background:<?= $isNew ? '#fffaf0' : '#fff' ?>; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); border:<?= $isNew ? '1px solid #f1d28a' : '1px solid transparent' ?>;">
                        <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                            <div>
                                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    <div style="font-size:16px; font-weight:800; color:#111;"><?= htmlspecialchars($order['order_number']) ?></div>
                                    <?php if ($isNew): ?>
                                        <span style="padding:5px 9px; border-radius:999px; font-size:10px; font-weight:800; background:#ffb300; color:#111;">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:13px; color:#666; margin-top:4px;"><?= htmlspecialchars($order['customer_name']) ?></div>
                                <div style="font-size:12px; color:#888; margin-top:4px;"><?= htmlspecialchars($order['phone'] ?? '') ?><?= !empty($order['email']) ? ' | ' . htmlspecialchars($order['email']) : '' ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:14px; font-weight:700; color:#111;"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) $order['total_amount'], 2) ?></div>
                                <div style="font-size:12px; color:#888; margin-top:4px;"><?= htmlspecialchars($order['created_at']) ?></div>
                            </div>
                        </div>
                        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <span style="padding:6px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#fff3dc; color:#9b5d00;">
                                <?= htmlspecialchars(strtoupper($order['payment_method'] ?? $order['payment_gateway'] ?? '')) ?>
                            </span>
                            <span style="padding:6px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#f3f0ff; color:#5b33d6;"><?= htmlspecialchars(strtoupper($order['payment_gateway'])) ?></span>
                            <span style="padding:6px 10px; border-radius:999px; font-size:11px; font-weight:700; background:<?= ($order['payment_status'] ?? '') === 'paid' ? '#e8fff0' : ((($order['payment_status'] ?? '') === 'cancelled') ? '#fff2ec' : '#f4f4f4') ?>; color:<?= ($order['payment_status'] ?? '') === 'paid' ? '#1a9b57' : ((($order['payment_status'] ?? '') === 'cancelled') ? '#d2552c' : '#666') ?>;">
                                <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $order['payment_status'] ?? 'pending'))) ?>
                            </span>
                            <span style="padding:6px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#eef5ff; color:#2463d0;">
                                <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $order['order_status'] ?? 'pending'))) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php $current_page = 'orders';
    include 'views/layouts/bottom_nav.php'; ?>
</body>
</html>
