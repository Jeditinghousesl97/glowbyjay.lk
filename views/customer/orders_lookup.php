<?php
$hide_mobile_welcome = true;
require_once ROOT_PATH . 'helpers/ImageHelper.php';
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

<div style="max-width: 920px; margin: 40px auto 0; padding: 0 0 48px;">
    <div style="background: #fff; border-radius: 28px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.06); margin-bottom: 24px;">
        <div style="margin-bottom: 18px;">
            <h1 style="margin: 0 0 8px; font-size: 28px; color: #111;">My Orders</h1>
            <p style="margin: 0; color: #666; line-height: 1.6;">Use the same email address and phone number you used when placing the order.</p>
        </div>

        <form method="get" action="<?= BASE_URL ?>order/myOrders" style="display: grid; gap: 14px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                <div>
                    <label for="lookupEmail" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Email Address</label>
                    <input type="email" id="lookupEmail" name="email" value="<?= htmlspecialchars($lookup_email ?? '') ?>" required style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:14px;">
                </div>
                <div>
                    <label for="lookupPhone" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Phone Number</label>
                    <input type="tel" id="lookupPhone" name="phone" value="<?= htmlspecialchars($lookup_phone ?? '') ?>" required style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:14px;">
                </div>
                <div>
                    <label for="lookupOrderNumber" style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Order Number (Optional)</label>
                    <input type="text" id="lookupOrderNumber" name="order_number" value="<?= htmlspecialchars($lookup_order_number ?? '') ?>" placeholder="ORD-20260313101910-12345" style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:14px;">
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" style="padding:12px 18px; border:none; border-radius:999px; background:#111; color:#fff; font-weight:700; cursor:pointer;">View My Orders</button>
                <a href="<?= BASE_URL ?>order/myOrders" style="padding:12px 18px; border-radius:999px; background:#f2f2f2; color:#222; text-decoration:none; font-weight:700;">Clear</a>
            </div>
        </form>
    </div>

    <?php if (!empty($lookup_error)): ?>
        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 16px; background: #fff4f2; color: #c44c35; font-size: 13px; font-weight: 600;">
            <?= htmlspecialchars($lookup_error) ?>
        </div>
    <?php endif; ?>

    <?php if (($lookup_attempted ?? false) && empty($lookup_error) && empty($orders)): ?>
        <div style="background: #fff; border-radius: 24px; padding: 24px; color: #666; box-shadow: 0 12px 28px rgba(0,0,0,0.05);">
            No orders were found for the details you entered.
        </div>
    <?php endif; ?>

    <?php foreach (($orders ?? []) as $order): ?>
        <?php
        $paymentLabel = ucfirst(str_replace('_', ' ', (string) ($order['payment_status'] ?? 'pending')));
        $orderLabel = ucfirst(str_replace('_', ' ', (string) ($order['order_status'] ?? 'pending')));
        $paymentMethod = strtolower(trim((string) ($order['payment_method'] ?? '')));
        $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? 'pending')));
        $orderStatus = strtolower(trim((string) ($order['order_status'] ?? 'pending')));
        $canRetryPayment = in_array($paymentMethod, ['payhere', 'koko'], true)
            && in_array($paymentStatus, ['failed', 'verification_failed', 'cancelled'], true)
            && $orderStatus !== 'cancelled';
        ?>
        <div style="background: #fff; border-radius: 24px; padding: 24px; box-shadow: 0 12px 28px rgba(0,0,0,0.05); margin-bottom: 18px;">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                <div>
                    <div style="font-size:12px; color:#777; margin-bottom:4px;">Order Number</div>
                    <div style="font-size:18px; font-weight:800; color:#111;"><?= htmlspecialchars($order['order_number']) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px; color:#777; margin-bottom:4px;">Placed On</div>
                    <div style="font-size:14px; font-weight:700; color:#222;"><?= htmlspecialchars(date('Y-m-d H:i', strtotime((string) $order['created_at']))) ?></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 18px;">
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Payment Status</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($paymentLabel) ?></div>
                </div>
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Order Status</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($orderLabel) ?></div>
                </div>
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Total Amount</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['total_amount'] ?? 0), 2) ?></div>
                </div>
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Courier Service</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['courier_service'] ?: '-') ?></div>
                </div>
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Tracking Number</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['tracking_number'] ?: '-') ?></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 18px;">
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Subtotal</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['subtotal_amount'] ?? 0), 2) ?></div>
                </div>
                <div style="background:#fafafa; border-radius:16px; padding:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Shipping Fee</div>
                    <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['shipping_fee'] ?? 0), 2) ?></div>
                </div>
                <?php if ((float) ($order['handling_fee'] ?? 0) > 0): ?>
                    <div style="background:#fafafa; border-radius:16px; padding:14px;">
                        <div style="font-size:12px; color:#777; margin-bottom:6px;">Handling Fee</div>
                        <div style="font-size:15px; font-weight:800; color:#111;"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['handling_fee'] ?? 0), 2) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display:grid; gap:12px;">
                <?php foreach (($order['items'] ?? []) as $item): ?>
                    <div style="display:flex; align-items:center; gap:12px; background:#fafafa; border-radius:18px; padding:12px;">
                        <?php if (!empty($item['image_url'])): ?>
                            <?php $lookupImageName = basename((string) parse_url((string) $item['image_url'], PHP_URL_PATH)); ?>
                            <?= ImageHelper::renderResponsivePicture(
                                $lookupImageName,
                                (string) $item['image_url'],
                                [
                                    'alt' => $item['product_title'] ?? 'Product',
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'fetchpriority' => 'low',
                                    'style' => 'width:62px; height:62px; border-radius:14px; object-fit:cover; background:#f0f0f0;'
                                ],
                                'admin_thumb'
                            ) ?>
                        <?php else: ?>
                            <div style="width:62px; height:62px; border-radius:14px; background:#f0f0f0;"></div>
                        <?php endif; ?>
                        <div style="flex:1;">
                            <div style="font-size:14px; font-weight:700; color:#111;"><?= htmlspecialchars($item['product_title'] ?? 'Product') ?></div>
                            <?php if (!empty($item['variant_text'])): ?>
                                <div style="font-size:12px; color:#666; margin-top:3px;"><?= htmlspecialchars($item['variant_text']) ?></div>
                            <?php endif; ?>
                            <div style="font-size:12px; color:#444; margin-top:6px;">Qty: <?= (int) ($item['qty'] ?? 1) ?> | LKR <?= number_format((float) ($item['line_total'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($canRetryPayment): ?>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                    <form method="post" action="<?= BASE_URL ?>order/<?= $paymentMethod === 'payhere' ? 'retryPayhere' : 'retryKoko' ?>" style="margin:0;">
                        <?= csrf_input() ?>
                        <input type="hidden" name="order_number" value="<?= htmlspecialchars($order['order_number']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($lookup_email ?? ($order['email'] ?? '')) ?>">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars($lookup_phone ?? ($order['phone'] ?? '')) ?>">
                        <button type="submit" style="padding:12px 18px; border:none; border-radius:999px; background:#111; color:#fff; font-weight:700; cursor:pointer;">
                            Pay Now
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('lookupEmail');
        const phoneInput = document.getElementById('lookupPhone');

        if (emailInput && !emailInput.value && localStorage.getItem('cus_email')) {
            emailInput.value = localStorage.getItem('cus_email');
        }

        if (phoneInput && !phoneInput.value && localStorage.getItem('cus_phone1')) {
            phoneInput.value = localStorage.getItem('cus_phone1');
        }
    });
</script>

<?php customer_layout_end(); ?>
