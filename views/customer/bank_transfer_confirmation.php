<?php
$hide_mobile_welcome = true;
require_once 'views/layouts/customer_layout.php';
customer_layout_start();
?>

<div style="max-width: 760px; margin: 60px auto 0; padding: 24px 0 48px;">
    <div style="background: #fff; border-radius: 28px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.06);">
        <div style="width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:18px; background:#edf4ff; color:#1f5aa6; font-size:28px;">
            <i class="fas fa-building-columns"></i>
        </div>
        <h1 style="margin:0 0 8px; font-size:30px; color:#111;">Bank Transfer Order Placed</h1>
        <p style="margin:0 0 24px; color:#666; line-height:1.7;">Your order has been placed. Please use the bank details below to complete the payment, then contact the shop owner with your payment reference if needed.</p>

        <?php if (!empty($order)): ?>
            <div style="display:grid; gap:12px; background:#fafafa; border-radius:20px; padding:20px; margin-bottom:18px;">
                <div><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></div>
                <div><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                <div><strong>Payment Method:</strong> Bank Transfer</div>
                <div><strong>Payment Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_status'] ?? 'pending'))) ?></div>
                <div><strong>Amount:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($settings['bank_transfer_details'])): ?>
            <div style="background:#f4f8ff; border:1px solid #d8e4ff; border-radius:20px; padding:20px;">
                <div style="font-size:15px; font-weight:800; color:#123b7a; margin-bottom:10px;">Bank Transfer Details</div>
                <div style="font-size:14px; color:#345; line-height:1.8; white-space:pre-wrap;"><?= nl2br(htmlspecialchars($settings['bank_transfer_details'])) ?></div>
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

            trackAnalyticsEvent('place_order', {
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>',
                transaction_id: '<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>',
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                payment_type: 'Bank Transfer'
            }, 'Lead', {
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>'
            });
        })();
    </script>
<?php endif; ?>

<?php customer_layout_end(); ?>
