<?php
$hide_mobile_welcome = true;
require_once 'views/layouts/customer_layout.php';
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

<div style="max-width: 760px; margin: 60px auto 0; padding: 24px 0 48px;">
    <div style="background: #fff; border-radius: 28px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.06);">
        <div style="width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:18px; background:#fff5e8; color:#c97b10; font-size:28px;">
            <i class="fas fa-box"></i>
        </div>
        <h1 style="margin:0 0 8px; font-size:30px; color:#111;">Order Placed</h1>
        <p style="margin:0 0 24px; color:#666; line-height:1.7;">Your Cash on Delivery order has been placed successfully. We will contact you before delivery if needed.</p>

        <?php if (!empty($order)): ?>
            <div style="display:grid; gap:12px; background:#fafafa; border-radius:20px; padding:20px;">
                <div><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></div>
                <div><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                <div><strong>Payment Method:</strong> Cash on Delivery</div>
                <div><strong>Order Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['order_status'] ?? 'pending'))) ?></div>
                <div><strong>Amount:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></div>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
            <a href="<?= BASE_URL ?>order/myOrders" style="padding:12px 18px; border-radius:999px; background:#111; color:#fff; text-decoration:none;">View My Orders</a>
            <a href="<?= BASE_URL ?>" style="padding:12px 18px; border-radius:999px; background:#f2f2f2; color:#222; text-decoration:none;">Back to Home</a>
        </div>
    </div>
</div>

<?php if (!empty($order)): ?>
    <script>
        (function () {
            try {
                localStorage.setItem('cus_email', '<?= htmlspecialchars($order['email'] ?? '', ENT_QUOTES) ?>');
                localStorage.setItem('cus_phone1', '<?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES) ?>');
            } catch (e) {
                console.warn('Could not save customer order lookup details.');
            }

            trackPurchaseOnce('<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>', {
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>',
                transaction_id: '<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>',
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                payment_type: 'Cash on Delivery'
            }, {
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>'
            });
        })();
    </script>
<?php endif; ?>

<?php customer_layout_end(); ?>
