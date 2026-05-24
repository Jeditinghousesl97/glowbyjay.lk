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

$shopWhatsappNumber = preg_replace('/[^0-9]/', '', (string) ($settings['shop_whatsapp'] ?? ''));
if ($shopWhatsappNumber === '') {
    $shopWhatsappNumber = preg_replace('/[^0-9]/', '', (string) ($settings['social_whatsapp'] ?? ''));
}
$bankTransferWhatsappUrl = '';
if (!empty($order) && $shopWhatsappNumber !== '') {
    $orderCurrency = (string) ($order['currency'] ?? 'LKR');
    $orderTotal = number_format((float) ($order['total_amount'] ?? 0), 2);
    $bankTransferMessage = "Hi, I sent the Bank Transfer receipt for my order.\n"
        . "Order Number: " . (string) ($order['order_number'] ?? '') . "\n"
        . "Customer Name: " . (string) ($order['customer_name'] ?? '') . "\n"
        . "Amount: " . $orderCurrency . " " . $orderTotal . "\n"
        . "Payment Method: Bank Transfer\n"
        . "Email: " . (string) ($order['email'] ?? '') . "\n"
        . "Phone: " . (string) ($order['phone'] ?? '') . "\n"
        . "Note: Please verify my transfer and confirm this order.";
    $bankTransferWhatsappUrl = 'https://wa.me/' . $shopWhatsappNumber . '?text=' . rawurlencode($bankTransferMessage);
}
?>

<div style="max-width: 760px; margin: 60px auto 0; padding: 24px 0 48px;">
    <div style="background: #fff; border-radius: 28px; padding: 28px; box-shadow: 0 16px 40px rgba(0,0,0,0.06);">
        <div style="display:flex; align-items:center; justify-content:flex-start; margin-bottom:18px;">
            <img src="<?= htmlspecialchars($orderTopIcon) ?>" alt="Website favicon" style="width:56px; height:56px; object-fit:contain;">
        </div>
        <h1 style="margin:0 0 8px; font-size:30px; color:#111; font-family:sans-serif;">Bank Transfer Order Placed</h1>
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
            <div style="margin:0 0 14px;">
                <img src="<?= htmlspecialchars(BASE_URL . 'assets/bank-details.jpg') ?>" alt="Bank details" style="display:block;width:100%;height:auto;border-radius:16px;object-fit:cover;">
            </div>
            <div style="background:#f7e7b3; border:1px solid #d4af37; border-radius:20px; padding:20px;">
                <div style="font-size:15px; font-weight:800; color:#000; margin-bottom:10px;">Bank Transfer Details</div>
                <div style="font-size:14px; color:#000; line-height:1.6; white-space:pre-wrap;"><?= htmlspecialchars($settings['bank_transfer_details']) ?></div>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:24px;">
            <?php if ($bankTransferWhatsappUrl !== ''): ?>
                <a href="<?= htmlspecialchars($bankTransferWhatsappUrl) ?>" target="_blank" rel="noopener" style="padding:12px 18px; border-radius:999px; background:#289b26; color:#fff; text-decoration:none;">Send Receipt via WhatsApp</a>
            <?php endif; ?>
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
