<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.52);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .modal-backdrop.is-open {
            display: flex;
        }

        .modal-card {
            width: min(100%, 440px);
            background: #fff;
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.18);
        }

        .modal-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            font-size: 14px;
            box-sizing: border-box;
            margin-top: 6px;
        }

        @media (min-width: 992px) {
            .order-details-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 20px !important;
            }

            .order-details-card {
                border-radius: 0 !important;
                padding: 24px !important;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06) !important;
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .order-items-card {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>
    <?php
    $courierOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($settings['courier_services_list'] ?? '')))));
    ?>
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2 style="margin:0;">Order Details</h2>
                <p style="margin:4px 0 0; font-size:12px; color:#888;"><?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            <a href="<?= BASE_URL ?>order/manage" style="text-decoration:none; color:#007aff; font-weight:700;">Back to Orders</a>
        </div>

        <div class="order-details-grid" style="display:grid; gap:18px;">
            <div class="order-details-card" style="background:#fff; border-radius:18px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                <h3 style="margin:0 0 14px;">Payment Summary</h3>
                <div style="display:grid; gap:10px; font-size:14px;">
                    <div><strong>Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_status'] ?? 'pending'))) ?></div>
                    <div><strong>Order Type:</strong> <?= htmlspecialchars(strtoupper($order['payment_method'] ?? $order['payment_gateway'] ?? '-')) ?></div>
                    <div><strong>Order Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['order_status'] ?? 'pending'))) ?></div>
                    <div><strong>Gateway:</strong> <?= htmlspecialchars(strtoupper($order['payment_gateway'])) ?></div>
                    <div><strong>Subtotal:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['subtotal_amount'] ?? 0), 2) ?></div>
                    <div><strong>Shipping Fee:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['shipping_fee'] ?? 0), 2) ?></div>
                    <div><strong>Handling Fee:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) ($order['handling_fee'] ?? 0), 2) ?></div>
                    <div><strong>Chargeable Weight:</strong> <?= number_format(((float) ($order['chargeable_weight_grams'] ?? 0)) / 1000, 2) ?> Kg</div>
                    <div><strong>Amount:</strong> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) $order['total_amount'], 2) ?></div>
                    <div><strong>Payment ID:</strong> <?= htmlspecialchars($order['gateway_payment_id'] ?: '-') ?></div>
                    <div><strong>Message:</strong> <?= htmlspecialchars($order['gateway_message'] ?: '-') ?></div>
                    <div><strong>Courier Service:</strong> <?= htmlspecialchars($order['courier_service'] ?: '-') ?></div>
                    <div><strong>Tracking Number:</strong> <?= htmlspecialchars($order['tracking_number'] ?: '-') ?></div>
                    <div><strong>Created:</strong> <?= htmlspecialchars($order['created_at']) ?></div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
                <?php if (($order['payment_method'] ?? '') === 'cod' && ($order['payment_status'] ?? 'pending') !== 'paid'): ?>
                    <form action="<?= BASE_URL ?>order/markPaymentReceived/<?= urlencode($order['order_number']) ?>" method="POST" onsubmit="return confirm('Mark COD payment as received?');">
                        <?= csrf_input() ?>
                        <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#1a9b57; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                            Payment Received
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (in_array(($order['payment_method'] ?? ''), ['payhere', 'koko', 'bank_transfer'], true) && ($order['payment_status'] ?? 'pending') !== 'paid'): ?>
                    <form action="<?= BASE_URL ?>order/markGatewayPaymentRecorded/<?= urlencode($order['order_number']) ?>" method="POST" onsubmit="return confirm('Record this payment as completed manually?');">
                        <?= csrf_input() ?>
                        <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#0b6cd1; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                            Record Payment
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (($order['order_status'] ?? 'pending') !== 'completed'): ?>
                    <button type="button" onclick="openCompleteOrderModal()" style="border:none; background:#111; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                            Mark Order as Completed
                    </button>
                <?php endif; ?>
                <?php if (($order['order_status'] ?? 'pending') !== 'cancelled'): ?>
                    <form action="<?= BASE_URL ?>order/cancel/<?= urlencode($order['order_number']) ?>" method="POST" onsubmit="return confirm('Cancel this order?');">
                        <?= csrf_input() ?>
                        <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#f39c12; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                            Cancel Order
                        </button>
                    </form>
                <?php endif; ?>
                    <form action="<?= BASE_URL ?>order/delete/<?= urlencode($order['order_number']) ?>" method="POST" onsubmit="return confirm('Delete this order permanently?');">
                        <?= csrf_input() ?>
                        <button type="submit" onclick="showGlobalLoader()" style="border:none; background:#e2552f; color:#fff; padding:12px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                            Delete Order
                        </button>
                    </form>
                </div>
            </div>

            <div class="order-details-card" style="background:#fff; border-radius:18px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                <h3 style="margin:0 0 14px;">Customer Details</h3>
                <div style="display:grid; gap:10px; font-size:14px;">
                    <div><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></div>
                    <div><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></div>
                    <div><strong>Alternate Phone:</strong> <?= htmlspecialchars($order['phone_alt'] ?: '-') ?></div>
                    <div><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></div>
                    <div><strong>City:</strong> <?= htmlspecialchars($order['city']) ?></div>
                    <div><strong>District:</strong> <?= htmlspecialchars($order['district'] ?: '-') ?></div>
                    <div><strong>Postal Code:</strong> <?= htmlspecialchars($order['postal_code'] ?: '-') ?></div>
                    <div><strong>Country:</strong> <?= htmlspecialchars($order['country']) ?></div>
                    <div><strong>Note:</strong> <?= htmlspecialchars($order['note'] ?: '-') ?></div>
                </div>
            </div>

            <div class="order-details-card order-items-card" style="background:#fff; border-radius:18px; padding:20px; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                <h3 style="margin:0 0 14px;">Items</h3>
                <div style="display:grid; gap:14px;">
                    <?php if (empty($order['items'])): ?>
                        <div style="padding:14px; border-radius:14px; background:#fafafa; color:#777;">No order items found.</div>
                    <?php else: ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div style="padding:14px; border-radius:14px; background:#fafafa;">
                                <div style="font-size:15px; font-weight:700; color:#111;"><?= htmlspecialchars($item['product_title']) ?></div>
                                <div style="font-size:12px; color:#666; margin-top:4px;"><?= htmlspecialchars($item['variant_text'] ?: '-') ?></div>
                                <div style="font-size:13px; color:#333; margin-top:8px;">
                                    Qty <?= (int) $item['qty'] ?> x <?= htmlspecialchars($order['currency']) ?> <?= number_format((float) $item['unit_price'], 2) ?>
                                    = <strong><?= htmlspecialchars($order['currency']) ?> <?= number_format((float) $item['line_total'], 2) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="completeOrderModal">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:16px;">
                <div>
                    <h3 style="margin:0;">Complete Order</h3>
                    <p style="margin:6px 0 0; font-size:12px; color:#777;">Tracking number and courier service are optional.</p>
                </div>
                <button type="button" onclick="closeCompleteOrderModal()" style="border:none; background:transparent; font-size:22px; cursor:pointer; line-height:1;">&times;</button>
            </div>

            <form action="<?= BASE_URL ?>order/markCompleted/<?= urlencode($order['order_number']) ?>" method="POST" onsubmit="showGlobalLoader()">
                <?= csrf_input() ?>
                <label style="display:block; font-size:13px; font-weight:700; color:#444; margin-bottom:14px;">
                    Tracking Number
                    <input type="text" name="tracking_number" class="modal-input" placeholder="Enter tracking number" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
                </label>

                <label style="display:block; font-size:13px; font-weight:700; color:#444; margin-bottom:18px;">
                    Courier Service
                    <select name="courier_service" class="modal-input">
                        <option value="">Select courier service</option>
                        <?php foreach ($courierOptions as $courierOption): ?>
                            <option value="<?= htmlspecialchars($courierOption) ?>" <?= (($order['courier_service'] ?? '') === $courierOption) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($courierOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                    <button type="button" onclick="closeCompleteOrderModal()" style="border:1px solid #ddd; background:#fff; color:#333; padding:11px 16px; border-radius:999px; font-weight:700; cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="border:none; background:#111; color:#fff; padding:11px 18px; border-radius:999px; font-weight:700; cursor:pointer;">
                        Complete Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php $current_page = 'orders';
    include 'views/layouts/bottom_nav.php'; ?>
    <script>
        function openCompleteOrderModal() {
            document.getElementById('completeOrderModal').classList.add('is-open');
        }

        function closeCompleteOrderModal() {
            document.getElementById('completeOrderModal').classList.remove('is-open');
        }
    </script>
</body>
</html>
