<?php
require_once 'models/Setting.php';
require_once 'models/Product.php';
require_once 'models/StockAlertNotification.php';
require_once 'helpers/SmtpMailer.php';
require_once 'helpers/SmsLenzClient.php';

class StockAlertService
{
    private $settingModel;
    private $productModel;
    private $notificationModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->productModel = new Product();
        $this->notificationModel = new StockAlertNotification();
    }

    public function syncProductAlerts($productId)
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return;
        }

        $product = $this->productModel->getById($productId);
        if (!$product) {
            return;
        }

        $variantRows = $this->productModel->getVariantStockRows($productId);
        if (empty($variantRows)) {
            $state = $this->productModel->getStockAlertState($productId);
            $this->syncState($state);
            return;
        }

        foreach ($variantRows as $row) {
            $state = $this->productModel->getStockAlertState($productId, (string) ($row['combination_key'] ?? ''));
            $this->syncState($state);
        }
    }

    public function syncAlertsForItems(array $items)
    {
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $state = $this->productModel->getStockAlertState($productId, (string) ($item['variant_key'] ?? ''));
            $this->syncState($state);
        }
    }

    private function syncState($state)
    {
        if (!is_array($state) || empty($state['product_id'])) {
            return;
        }

        $productId = (int) $state['product_id'];
        $variantKey = (string) ($state['variant_key'] ?? '');
        $status = (string) ($state['status'] ?? 'in_stock');

        if ($status !== 'low_stock' && $status !== 'out_of_stock') {
            $this->notificationModel->resolve($productId, $variantKey);
            return;
        }

        $opposite = $status === 'low_stock' ? 'out_of_stock' : 'low_stock';
        $this->notificationModel->resolve($productId, $variantKey, $opposite);

        if ($this->notificationModel->isActive($productId, $variantKey, $status)) {
            return;
        }

        $this->sendOwnerEmail($state);
        $this->sendOwnerSms($state);
        $this->notificationModel->activate($productId, $variantKey, $status);
    }

    private function sendOwnerEmail(array $state)
    {
        $settings = $this->settingModel->getAllPairs();
        $ownerEmail = trim((string) ($settings['shop_owner_email'] ?? ''));
        if ($ownerEmail === '' || empty($settings['smtp_host']) || empty($settings['smtp_port']) || empty($settings['smtp_from_email'])) {
            return;
        }

        $label = $this->stateLabel($state);
        $subject = '[' . ($settings['shop_name'] ?? 'Shop') . '] ' . ucfirst(str_replace('_', ' ', (string) $state['status'])) . ' alert';
        $body = '<p>' . htmlspecialchars($label) . '</p>'
            . '<p>Status: <strong>' . htmlspecialchars(str_replace('_', ' ', (string) $state['status'])) . '</strong></p>'
            . '<p>Available Qty: <strong>' . htmlspecialchars($state['stock_qty'] === null ? 'Unlimited' : (string) $state['stock_qty']) . '</strong></p>'
            . '<p>Threshold: <strong>' . htmlspecialchars((string) ($state['threshold'] ?? 0)) . '</strong></p>';

        $mailer = new SmtpMailer();
        try {
            $mailer->send($settings, $ownerEmail, $settings['shop_name'] ?? 'Shop Owner', $subject, $body, strip_tags(str_replace('</p>', PHP_EOL, $body)));
        } catch (Exception $e) {
            $this->logFailure('email', $e->getMessage(), $state);
        }
    }

    private function sendOwnerSms(array $state)
    {
        $settings = $this->settingModel->getAllPairs();
        if (empty($settings['sms_owner_enabled'])) {
            return;
        }

        $recipient = preg_replace('/[^0-9]/', '', (string) ($settings['shop_whatsapp'] ?? $settings['social_whatsapp'] ?? ''));
        if ($recipient === '' || empty($settings['sms_user_id']) || empty($settings['sms_api_key']) || empty($settings['sms_sender_id'])) {
            return;
        }

        $message = $this->stateLabel($state)
            . ' is ' . str_replace('_', ' ', (string) $state['status'])
            . '. Qty: ' . ($state['stock_qty'] === null ? 'Unlimited' : (string) $state['stock_qty'])
            . ', Threshold: ' . (string) ($state['threshold'] ?? 0);

        try {
            $client = new SmsLenzClient();
            $client->send($settings, $recipient, $message);
        } catch (Exception $e) {
            $this->logFailure('sms', $e->getMessage(), $state);
        }
    }

    private function stateLabel(array $state)
    {
        if (!empty($state['is_variant'])) {
            return (string) ($state['product_title'] ?? 'Product') . ' [' . (string) ($state['variant_label'] ?? $state['variant_key'] ?? 'Variant') . ']';
        }

        return (string) ($state['product_title'] ?? 'Product');
    }

    private function logFailure($channel, $message, array $state)
    {
        $logDir = ROOT_PATH . 'storage/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . 'stock_alerts.log',
            json_encode([
                'time' => date('c'),
                'channel' => $channel,
                'message' => $message,
                'state' => $state
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
