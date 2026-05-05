<?php
require_once 'models/Setting.php';
require_once 'models/EmailNotification.php';
require_once 'helpers/SeoHelper.php';
require_once 'helpers/SmtpMailer.php';

class OrderEmailService
{
    private $settingModel;
    private $notificationModel;
    private $customerBodyDefaults = [
        'order_placed' => 'Your order has been created successfully and is now in our system.',
        'payment_completed' => 'Your payment was completed successfully. We can now process your order.',
        'payment_cancelled' => 'The payment for your order was cancelled.',
        'payment_failed' => 'We could not confirm payment for your order.',
        'payment_received' => 'We have marked your order payment as received.',
        'order_completed' => 'Your order has been marked as completed. Courier: {courier_service}. Tracking Number: {tracking_number}.',
        'order_cancelled' => 'Your order has been cancelled.',
    ];
    private $ownerBodyDefaults = [
        'order_placed' => 'A new order has just been placed in your shop.',
        'payment_completed' => 'A payment has been completed for an order in your shop.',
        'payment_cancelled' => 'A customer payment was cancelled.',
        'payment_failed' => 'A customer payment failed and needs attention.',
        'payment_received' => 'Cash on delivery payment has been marked as received.',
        'order_completed' => 'An order has been marked as completed. Courier: {courier_service}. Tracking Number: {tracking_number}.',
        'order_cancelled' => 'An order has been cancelled.',
    ];

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->notificationModel = new EmailNotification();
    }

    public function sendForEvent(array $order, $eventKey)
    {
        if (empty($order['id'])) {
            return;
        }

        $settings = $this->settingModel->getAllPairs();
        if (empty($settings['smtp_host']) || empty($settings['smtp_port']) || empty($settings['smtp_from_email'])) {
            return;
        }

        $mailer = new SmtpMailer();
        $recipients = $this->resolveRecipients($order, $settings);
        $emailData = $this->buildEmailData($order, $settings, $eventKey);

        foreach ($recipients as $recipient) {
            $email = trim((string) ($recipient['email'] ?? ''));
            if ($email === '' || empty($emailData[$recipient['type']])) {
                continue;
            }

            if ($this->notificationModel->wasSent((int) $order['id'], $eventKey, $email)) {
                continue;
            }

            $content = $emailData[$recipient['type']];
            try {
                $mailer->send($settings, $email, $recipient['name'] ?? '', $content['subject'], $content['html'], $content['text']);
                $this->notificationModel->markSent((int) $order['id'], $eventKey, $email);
            } catch (Exception $e) {
                $this->logFailure($eventKey, $email, $e->getMessage());
            }
        }
    }

    private function resolveRecipients(array $order, array $settings)
    {
        $recipients = [];

        if (!empty($order['email'])) {
            $recipients[] = [
                'type' => 'customer',
                'email' => $order['email'],
                'name' => $order['customer_name'] ?? 'Customer'
            ];
        }

        if (!empty($settings['shop_owner_email'])) {
            $recipients[] = [
                'type' => 'owner',
                'email' => $settings['shop_owner_email'],
                'name' => $settings['shop_name'] ?? 'Shop Owner'
            ];
        }

        return $recipients;
    }

    private function buildEmailData(array $order, array $settings, $eventKey)
    {
        $shopName = SeoHelper::shopName($settings);
        $currency = $order['currency'] ?? ($settings['currency_symbol'] ?? 'LKR');
        $statusLabel = ucfirst(str_replace('_', ' ', (string) ($order['order_status'] ?? 'pending')));
        $paymentLabel = ucfirst(str_replace('_', ' ', (string) ($order['payment_status'] ?? 'pending')));
        $methodLabel = strtoupper((string) ($order['payment_method'] ?? $order['payment_gateway'] ?? 'ORDER'));

        $customerSubject = $shopName . ' Order Update';
        $customerHeading = 'Order Update';
        $customerIntro = $this->buildBodyContent($settings, $order, $eventKey, 'customer');
        $ownerSubject = 'New order activity - ' . ($order['order_number'] ?? '');
        $ownerHeading = 'Order Notification';
        $ownerIntro = $this->buildBodyContent($settings, $order, $eventKey, 'owner');

        switch ($eventKey) {
            case 'order_placed':
                $customerSubject = $shopName . ' order received - ' . $order['order_number'];
                $customerHeading = 'We Received Your Order';
                $ownerSubject = 'New order received - ' . $order['order_number'];
                $ownerHeading = 'New Order Received';
                break;
            case 'payment_completed':
                $customerSubject = $shopName . ' payment completed - ' . $order['order_number'];
                $customerHeading = 'Payment Completed';
                $ownerSubject = 'Payment completed - ' . $order['order_number'];
                $ownerHeading = 'Payment Completed';
                break;
            case 'payment_cancelled':
                $customerSubject = $shopName . ' payment cancelled - ' . $order['order_number'];
                $customerHeading = 'Payment Cancelled';
                $ownerSubject = 'Payment cancelled - ' . $order['order_number'];
                $ownerHeading = 'Payment Cancelled';
                break;
            case 'payment_failed':
                $customerSubject = $shopName . ' payment failed - ' . $order['order_number'];
                $customerHeading = 'Payment Failed';
                $ownerSubject = 'Payment failed - ' . $order['order_number'];
                $ownerHeading = 'Payment Failed';
                break;
            case 'payment_received':
                $customerSubject = $shopName . ' payment received - ' . $order['order_number'];
                $customerHeading = 'Payment Received';
                $ownerSubject = 'COD payment received - ' . $order['order_number'];
                $ownerHeading = 'COD Payment Received';
                break;
            case 'order_completed':
                $customerSubject = $shopName . ' order completed - ' . $order['order_number'];
                $customerHeading = 'Order Completed';
                $ownerSubject = 'Order completed - ' . $order['order_number'];
                $ownerHeading = 'Order Completed';
                break;
            case 'order_cancelled':
                $customerSubject = $shopName . ' order cancelled - ' . $order['order_number'];
                $customerHeading = 'Order Cancelled';
                $ownerSubject = 'Order cancelled - ' . $order['order_number'];
                $ownerHeading = 'Order Cancelled';
                break;
        }

        $customerHtml = $this->renderTemplate($settings, $customerHeading, $customerIntro, $order, $currency, $statusLabel, $paymentLabel, $methodLabel, true);
        $ownerHtml = $this->renderTemplate($settings, $ownerHeading, $ownerIntro, $order, $currency, $statusLabel, $paymentLabel, $methodLabel, false);

        return [
            'customer' => [
                'subject' => $customerSubject,
                'html' => $customerHtml,
                'text' => $this->textVersion($customerHeading, $customerIntro, $order, $currency, $statusLabel, $paymentLabel, $methodLabel)
            ],
            'owner' => [
                'subject' => $ownerSubject,
                'html' => $ownerHtml,
                'text' => $this->textVersion($ownerHeading, $ownerIntro, $order, $currency, $statusLabel, $paymentLabel, $methodLabel)
            ]
        ];
    }

    private function renderTemplate(array $settings, $heading, $intro, array $order, $currency, $statusLabel, $paymentLabel, $methodLabel, $forCustomer)
    {
        $logoUrl = SeoHelper::normalizeAssetUrl($settings['shop_logo'] ?? '');
        $shopName = SeoHelper::shopName($settings);
        $shopAbout = nl2br(htmlspecialchars((string) ($settings['shop_about'] ?? '')));
        $shopWhatsapp = htmlspecialchars((string) ($settings['shop_whatsapp'] ?? ''));
        $siteUrl = SeoHelper::absoluteUrl(BASE_URL);
        $itemsHtml = '';

        foreach (($order['items'] ?? []) as $item) {
            $itemsHtml .= '<tr>'
                . '<td style="padding:10px 0;border-bottom:1px solid #f0f0f0;">' . htmlspecialchars($item['product_title'] ?? 'Product') . '<br><span style="font-size:12px;color:#777;">' . htmlspecialchars($item['variant_text'] ?? '-') . '</span></td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #f0f0f0;text-align:center;">' . (int) ($item['qty'] ?? 1) . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #f0f0f0;text-align:right;">' . htmlspecialchars($currency) . ' ' . number_format((float) ($item['line_total'] ?? 0), 2) . '</td>'
                . '</tr>';
        }

        if ($itemsHtml === '') {
            $itemsHtml = '<tr><td colspan="3" style="padding:10px 0;color:#777;">No items recorded.</td></tr>';
        }

        $customerBlock = $forCustomer ? '' : '
            <div style="margin-top:24px; padding:16px; background:#fafafa; border-radius:16px;">
                <div style="font-weight:700; margin-bottom:8px;">Customer Details</div>
                <div style="font-size:14px; color:#444; line-height:1.7;">
                    ' . htmlspecialchars($order['customer_name'] ?? '-') . '<br>
                    ' . htmlspecialchars($order['email'] ?? '-') . '<br>
                    ' . htmlspecialchars($order['phone'] ?? '-') . '<br>
                    ' . htmlspecialchars($order['address'] ?? '-') . ', ' . htmlspecialchars($order['city'] ?? '-') . '
                </div>
            </div>';

        return '
            <div style="margin:0; padding:24px; background:#f4f6fb; font-family:Arial,sans-serif; color:#111;">
                <div style="max-width:680px; margin:0 auto; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 12px 32px rgba(0,0,0,0.08);">
                    <div style="padding:28px 28px 18px; background:linear-gradient(135deg,#111111,#2f5dff); color:#fff;">
                        ' . (!empty($logoUrl) ? '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($shopName) . '" style="width:64px;height:64px;border-radius:16px;object-fit:cover;background:#fff;padding:6px;margin-bottom:14px;">' : '') . '
                        <div style="font-size:24px; font-weight:800; margin-bottom:6px;">' . htmlspecialchars($shopName) . '</div>
                        <div style="font-size:14px; opacity:0.88;">' . htmlspecialchars($heading) . '</div>
                    </div>
                    <div style="padding:28px;">
                        <p style="margin:0 0 18px; font-size:15px; line-height:1.7; color:#444;">' . nl2br(htmlspecialchars($intro)) . '</p>
                        <div style="display:grid; gap:10px; background:#fafafa; border-radius:18px; padding:18px; margin-bottom:20px;">
                            <div><strong>Order Number:</strong> ' . htmlspecialchars($order['order_number'] ?? '-') . '</div>
                            <div><strong>Order Type:</strong> ' . htmlspecialchars($methodLabel) . '</div>
                            <div><strong>Payment Status:</strong> ' . htmlspecialchars($paymentLabel) . '</div>
                            <div><strong>Order Status:</strong> ' . htmlspecialchars($statusLabel) . '</div>
                            <div><strong>Courier Service:</strong> ' . htmlspecialchars($order['courier_service'] ?? '-') . '</div>
                            <div><strong>Tracking Number:</strong> ' . htmlspecialchars($order['tracking_number'] ?? '-') . '</div>
                            <div><strong>Total:</strong> ' . htmlspecialchars($currency) . ' ' . number_format((float) ($order['total_amount'] ?? 0), 2) . '</div>
                        </div>
                        <div style="font-size:15px; font-weight:700; margin-bottom:10px;">Order Items</div>
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; padding:0 0 10px; color:#666;">Item</th>
                                    <th style="text-align:center; padding:0 0 10px; color:#666;">Qty</th>
                                    <th style="text-align:right; padding:0 0 10px; color:#666;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>' . $itemsHtml . '</tbody>
                        </table>
                        ' . $customerBlock . '
                        <div style="margin-top:24px; padding-top:18px; border-top:1px solid #f0f0f0; color:#666; font-size:13px; line-height:1.7;">
                            ' . ($shopAbout !== '' ? '<div style="margin-bottom:8px;">' . $shopAbout . '</div>' : '') . '
                            ' . ($shopWhatsapp !== '' ? '<div>WhatsApp: ' . $shopWhatsapp . '</div>' : '') . '
                            <div>Website: <a href="' . htmlspecialchars($siteUrl) . '" style="color:#2f5dff; text-decoration:none;">' . htmlspecialchars($siteUrl) . '</a></div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function textVersion($heading, $intro, array $order, $currency, $statusLabel, $paymentLabel, $methodLabel)
    {
        $lines = [
            $heading,
            '',
            $intro,
            '',
            'Order Number: ' . ($order['order_number'] ?? '-'),
            'Order Type: ' . $methodLabel,
            'Payment Status: ' . $paymentLabel,
            'Order Status: ' . $statusLabel,
            'Courier Service: ' . ($order['courier_service'] ?? '-'),
            'Tracking Number: ' . ($order['tracking_number'] ?? '-'),
            'Total: ' . $currency . ' ' . number_format((float) ($order['total_amount'] ?? 0), 2),
            ''
        ];

        foreach (($order['items'] ?? []) as $item) {
            $lines[] = '- ' . ($item['product_title'] ?? 'Product') . ' x ' . (int) ($item['qty'] ?? 1) . ' = ' . $currency . ' ' . number_format((float) ($item['line_total'] ?? 0), 2);
        }

        return implode("\n", $lines);
    }

    private function buildBodyContent(array $settings, array $order, $eventKey, $recipientType)
    {
        $settingKey = 'email_' . $recipientType . '_template_' . $eventKey;
        $template = trim((string) ($settings[$settingKey] ?? ''));
        if ($template === '') {
            $template = $recipientType === 'owner'
                ? ($this->ownerBodyDefaults[$eventKey] ?? 'There is an update on a customer order.')
                : ($this->customerBodyDefaults[$eventKey] ?? 'We have an update for your order.');
        }

        $content = $this->replacePlaceholders($template, $settings, $order);
        if (
            $recipientType === 'customer'
            && $eventKey === 'order_placed'
            && strtolower((string) ($order['payment_method'] ?? '')) === 'bank_transfer'
            && trim((string) ($settings['bank_transfer_details'] ?? '')) !== ''
        ) {
            $content .= "\n\nBank Transfer Details:\n" . trim((string) $settings['bank_transfer_details']);
        }

        return $content;
    }

    private function replacePlaceholders($text, array $settings, array $order)
    {
        $placeholders = [
            '{shop_name}' => SeoHelper::shopName($settings),
            '{customer_name}' => (string) ($order['customer_name'] ?? 'Customer'),
            '{order_number}' => (string) ($order['order_number'] ?? ''),
            '{currency}' => (string) ($order['currency'] ?? ($settings['currency_symbol'] ?? 'LKR')),
            '{total_amount}' => number_format((float) ($order['total_amount'] ?? 0), 2),
            '{payment_status}' => ucfirst(str_replace('_', ' ', (string) ($order['payment_status'] ?? 'pending'))),
            '{order_status}' => ucfirst(str_replace('_', ' ', (string) ($order['order_status'] ?? 'pending'))),
            '{payment_method}' => strtoupper((string) ($order['payment_method'] ?? $order['payment_gateway'] ?? 'ORDER')),
            '{courier_service}' => (string) ($order['courier_service'] ?? ''),
            '{tracking_number}' => (string) ($order['tracking_number'] ?? ''),
            '{shop_whatsapp}' => (string) ($settings['shop_whatsapp'] ?? ''),
            '{website_url}' => SeoHelper::absoluteUrl(BASE_URL),
            '{customer_email}' => (string) ($order['email'] ?? ''),
            '{customer_phone}' => (string) ($order['phone'] ?? ''),
            '{customer_address}' => trim(((string) ($order['address'] ?? '')) . ', ' . ((string) ($order['city'] ?? ''))),
            '{bank_transfer_details}' => trim((string) ($settings['bank_transfer_details'] ?? ''))
        ];

        return strtr($text, $placeholders);
    }

    private function logFailure($eventKey, $recipientEmail, $message)
    {
        $logDir = ROOT_PATH . 'storage/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . 'email.log',
            json_encode([
                'time' => date('c'),
                'event' => $eventKey,
                'recipient' => $recipientEmail,
                'message' => $message
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
