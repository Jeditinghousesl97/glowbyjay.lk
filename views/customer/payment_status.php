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

$orderTopIcon = !empty($settings['shop_favicon'])
    ? ImageHelper::settingsImageUrl($settings['shop_favicon'], str_replace('/Ecom-CMS/', BASE_URL, (string) $settings['shop_favicon']))
    : (!empty($settings['shop_logo'])
        ? ImageHelper::settingsImageUrl($settings['shop_logo'], str_replace('/Ecom-CMS/', BASE_URL, (string) $settings['shop_logo']))
        : BASE_URL . 'assets/images/placeholder.png');

$gatewayName = strtoupper((string) ($gateway_name ?? (($order['payment_gateway'] ?? '') === 'koko' ? 'KOKO' : 'Card Payment')));
$paymentStatus = $order['payment_status'] ?? 'unknown';
$isSuccess = $paymentStatus === 'paid';
$isCancelled = $paymentStatus === 'cancelled';
$isFailed = in_array($paymentStatus, ['failed', 'verification_failed'], true);
$isAwaitingConfirmation = !$isSuccess && !$isCancelled && !$isFailed && (($status_type ?? '') === 'return');

$heading = $isSuccess
    ? 'Payment Completed'
    : ($isCancelled
        ? 'Payment Cancelled'
        : ($isFailed
            ? 'Payment Failed'
            : ($isAwaitingConfirmation ? 'Payment Submitted' : 'Payment Status Pending')));

$message = $isSuccess
    ? 'Your payment was completed successfully. We can now process your order.'
    : ($isCancelled
        ? 'Your payment was cancelled. You can return to the cart and try again anytime.'
        : ($isFailed
            ? 'Your payment could not be completed. You can return to the cart and try again with the same or another payment method.'
            : ($isAwaitingConfirmation
                ? 'Your payment was submitted successfully. We are waiting for final confirmation from ' . $gatewayName . '. This page will refresh automatically.'
                : 'We are still waiting for final confirmation from ' . $gatewayName . '. This page will refresh automatically.')));

$displayStatus = $isSuccess
    ? 'Payment Completed'
    : ($isCancelled
        ? 'Payment Cancelled'
        : ($isFailed
            ? 'Payment Failed'
            : ($isAwaitingConfirmation ? 'Awaiting Payment Confirmation' : ucfirst(str_replace('_', ' ', $paymentStatus)))));
?>

<div style="max-width: 760px; margin: 60px auto 0; padding: 24px 0 48px;">
    <div style="background: #fff; border-radius: 28px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.06);">
        <div style="display:flex; align-items:center; justify-content:flex-start; margin-bottom:18px;">
            <img src="<?= htmlspecialchars($orderTopIcon) ?>" alt="Website favicon" style="width:56px; height:56px; object-fit:contain;">
        </div>
        <h1 style="margin:0 0 8px; font-size:30px; color:#111;"><?= htmlspecialchars($heading) ?></h1>
        <p style="margin:0 0 24px; color:#666; line-height:1.7;"><?= htmlspecialchars($message) ?></p>

        <?php if (!empty($order)): ?>
            <div style="display:grid; gap:12px; background:#fafafa; border-radius:20px; padding:20px;">
                <div><strong>Order Number:</strong> <?= htmlspecialchars($order['order_number']) ?></div>
                <div><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                <div><strong>Subtotal:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['subtotal_amount'] ?? 0), 2) ?></div>
                <div><strong>Shipping Fee:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['shipping_fee'] ?? 0), 2) ?></div>
                <?php if ((float) ($order['handling_fee'] ?? 0) > 0): ?>
                    <div><strong>Handling Fee:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['handling_fee'] ?? 0), 2) ?></div>
                <?php endif; ?>
                <div><strong>Amount:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></div>
                <div><strong>Payment Status:</strong> <?= htmlspecialchars($displayStatus) ?></div>
                <div><strong>Gateway:</strong> <?= htmlspecialchars($gatewayName) ?></div>
                <?php if (!empty($order['gateway_payment_id'])): ?>
                    <div><strong><?= htmlspecialchars($gatewayName) ?> Payment ID:</strong> <?= htmlspecialchars($order['gateway_payment_id']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['gateway_message'])): ?>
                    <div><strong>Message:</strong> <?= htmlspecialchars($order['gateway_message']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
            <a href="<?= BASE_URL ?>" style="padding:12px 18px; border-radius:999px; background:#111; color:#fff; text-decoration:none;">Back to Home</a>
            <a href="<?= BASE_URL ?>order/myOrders<?= !empty($order['email']) && !empty($order['phone']) ? '?' . http_build_query(['email' => $order['email'], 'phone' => $order['phone'], 'order_number' => $order['order_number'] ?? '']) : '' ?>" style="padding:12px 18px; border-radius:999px; background:#f2f2f2; color:#222; text-decoration:none;">My Orders</a>
        </div>
    </div>
</div>

<?php if (!$isSuccess && !$isCancelled && !$isFailed): ?>
    <script>
        setTimeout(function () {
            window.location.reload();
        }, 5000);
    </script>
<?php endif; ?>

<?php if (!empty($order)): ?>
    <script>
        (function () {
            try {
                localStorage.setItem('cus_email', '<?= htmlspecialchars($order['email'] ?? '', ENT_QUOTES) ?>');
                localStorage.setItem('cus_phone1', '<?= htmlspecialchars($order['phone'] ?? '', ENT_QUOTES) ?>');
            } catch (e) {
                console.warn('Could not save customer order lookup details.');
            }

            <?php if ($isSuccess): ?>
            trackPurchaseOnce('<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>', {
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>',
                transaction_id: '<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>',
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                shipping: <?= json_encode((float) ($order['shipping_fee'] ?? 0)) ?>,
                payment_type: '<?= htmlspecialchars($gatewayName, ENT_QUOTES) ?>'
            }, {
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>,
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>'
            });
            <?php elseif ($isFailed): ?>
            trackAnalyticsEvent('payment_failed', {
                currency: '<?= htmlspecialchars($order['currency'] ?? 'LKR', ENT_QUOTES) ?>',
                transaction_id: '<?= htmlspecialchars($order['order_number'] ?? '', ENT_QUOTES) ?>',
                payment_type: '<?= htmlspecialchars($gatewayName, ENT_QUOTES) ?>',
                value: <?= json_encode((float) ($order['total_amount'] ?? 0)) ?>
            });
            <?php endif; ?>
        })();
    </script>
<?php endif; ?>

<?php customer_layout_end(); ?>
