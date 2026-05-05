<?php
require_once 'models/Setting.php';
require_once 'models/SmsNotification.php';
require_once 'models/SmsQueue.php';
require_once 'helpers/SeoHelper.php';
require_once 'helpers/SmsLenzClient.php';

class OrderSmsService
{
    private $settingModel;
    private $notificationModel;
    private $queueModel;
    private $client;

    private $defaultTemplates = [
        'order_placed' => 'Hi {customer_name}, your order {order_number} at {shop_name} has been placed. Total: {currency} {total_amount}.',
        'payment_completed' => 'Good news {customer_name}. Payment completed for order {order_number} at {shop_name}.',
        'payment_cancelled' => 'Your payment was cancelled for order {order_number} at {shop_name}.',
        'payment_failed' => 'We could not confirm payment for order {order_number} at {shop_name}. Please try again or contact us.',
        'payment_received' => 'Payment received for your order {order_number} at {shop_name}. Thank you.',
        'order_completed' => 'Your order {order_number} from {shop_name} is completed. Courier: {courier_service}. Tracking: {tracking_number}.',
        'order_cancelled' => 'Your order {order_number} from {shop_name} has been cancelled.',
        'owner_order_received' => 'New order {order_number} received at {shop_name} from {customer_name}. Total: {currency} {total_amount}.',
    ];

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->notificationModel = new SmsNotification();
        $this->queueModel = new SmsQueue();
        $this->client = new SmsLenzClient();
    }

    public function getDefaultTemplate($eventKey)
    {
        return $this->defaultTemplates[$eventKey] ?? '';
    }

    public function queueForEvent(array $order, $eventKey)
    {
        if (empty($order['id'])) {
            return;
        }

        $settings = $this->settingModel->getAllPairs();
        if (!$this->isCustomerEnabled($settings) && !$this->isOwnerEnabled($settings)) {
            $this->writeLog([
                'time' => date('c'),
                'event' => $eventKey,
                'status' => 'skipped_not_configured',
                'order_id' => (int) $order['id']
            ]);
            return;
        }

        if ($this->isCustomerEnabled($settings)) {
            $this->queueModel->enqueue((int) $order['id'], $eventKey, 'customer');
            $this->writeLog([
                'time' => date('c'),
                'event' => $eventKey,
                'status' => 'queued',
                'recipient_type' => 'customer',
                'order_id' => (int) $order['id']
            ]);
        }

        if ($eventKey === 'order_placed' && $this->isOwnerEnabled($settings)) {
            $this->queueModel->enqueue((int) $order['id'], 'owner_order_received', 'owner');
            $this->writeLog([
                'time' => date('c'),
                'event' => 'owner_order_received',
                'status' => 'queued',
                'recipient_type' => 'owner',
                'order_id' => (int) $order['id']
            ]);
        }
    }

    public function sendDirectForEvent(array $order, $eventKey)
    {
        if (empty($order['id'])) {
            return;
        }

        $settings = $this->settingModel->getAllPairs();
        if ($this->isCustomerEnabled($settings)) {
            $this->sendToRecipient($order, $settings, $eventKey, 'customer');
        } else {
            $this->writeLog([
                'time' => date('c'),
                'event' => $eventKey,
                'status' => 'customer_skipped_not_configured',
                'order_id' => (int) $order['id']
            ]);
        }

        if ($eventKey === 'order_placed' && $this->isOwnerEnabled($settings)) {
            $this->sendToRecipient($order, $settings, 'owner_order_received', 'owner');
        }
    }

    public function processQueue($limit = 10)
    {
        $settings = $this->settingModel->getAllPairs();
        $jobs = $this->queueModel->claimNextBatch($limit);
        if (empty($jobs)) {
            $this->writeLog([
                'time' => date('c'),
                'event' => 'queue_worker',
                'status' => 'no_jobs'
            ]);
        }

        foreach ($jobs as $job) {
            $order = $this->loadOrder((int) ($job['order_id'] ?? 0));
            if (!$order) {
                $this->queueModel->markFailed((int) $job['id'], 'Order not found.');
                continue;
            }

            $recipientType = (string) ($job['recipient_type'] ?? 'customer');
            $recipientPhone = $this->resolveRecipientPhone($order, $settings, $recipientType);
            if ($recipientPhone === '') {
                $this->queueModel->markFailed((int) $job['id'], 'Recipient phone missing or invalid.');
                $this->logFailure((string) $job['event_key'], $recipientPhone, 'Recipient phone missing or invalid.');
                continue;
            }

            if ($this->notificationModel->wasSent((int) $order['id'], (string) $job['event_key'], $recipientPhone)) {
                $this->queueModel->markSent((int) $job['id']);
                continue;
            }

            $message = $this->buildMessage($order, $settings, (string) $job['event_key'], $recipientType);
            if ($message === '') {
                $this->queueModel->markFailed((int) $job['id'], 'SMS template resolved to empty message.');
                continue;
            }

            try {
                $response = $this->client->send($settings, $recipientPhone, $message);
                $this->notificationModel->markSent((int) $order['id'], (string) $job['event_key'], $recipientPhone);
                $this->queueModel->markSent((int) $job['id']);
                $this->logSuccess((string) $job['event_key'], $recipientPhone, $response['body'] ?? '');
            } catch (Exception $e) {
                $this->queueModel->markFailed((int) $job['id'], $e->getMessage());
                $this->logFailure((string) $job['event_key'], $recipientPhone, $e->getMessage());
            }
        }
    }

    private function isCustomerEnabled(array $settings)
    {
        return !empty($settings['sms_enabled'])
            && !empty($settings['sms_user_id'])
            && !empty($settings['sms_api_key'])
            && !empty($settings['sms_sender_id']);
    }

    private function isOwnerEnabled(array $settings)
    {
        return !empty($settings['sms_owner_enabled'])
            && !empty($settings['shop_whatsapp'])
            && !empty($settings['sms_user_id'])
            && !empty($settings['sms_api_key'])
            && !empty($settings['sms_sender_id']);
    }

    private function loadOrder($orderId)
    {
        if ($orderId <= 0) {
            return null;
        }

        require_once 'models/Order.php';
        $orderModel = new Order();
        return $orderModel->getById($orderId);
    }

    private function resolveRecipientPhone(array $order, array $settings, $recipientType)
    {
        if ($recipientType === 'owner') {
            return $this->normalizePhone((string) ($settings['shop_whatsapp'] ?? ''));
        }

        return $this->normalizePhone((string) ($order['phone'] ?? ''));
    }

    private function sendToRecipient(array $order, array $settings, $eventKey, $recipientType)
    {
        $recipientPhone = $this->resolveRecipientPhone($order, $settings, $recipientType);
        if ($recipientPhone === '') {
            $this->logFailure($eventKey, $recipientPhone, 'Recipient phone missing or invalid.');
            return;
        }

        if ($this->notificationModel->wasSent((int) $order['id'], (string) $eventKey, $recipientPhone)) {
            $this->writeLog([
                'time' => date('c'),
                'event' => $eventKey,
                'recipient' => $recipientPhone,
                'status' => 'already_sent',
                'recipient_type' => $recipientType,
                'order_id' => (int) $order['id']
            ]);
            return;
        }

        $message = $this->buildMessage($order, $settings, (string) $eventKey, $recipientType);
        if ($message === '') {
            $this->logFailure($eventKey, $recipientPhone, 'SMS template resolved to empty message.');
            return;
        }

        try {
            $response = $this->client->send($settings, $recipientPhone, $message);
            $this->notificationModel->markSent((int) $order['id'], (string) $eventKey, $recipientPhone);
            $this->logSuccess((string) $eventKey, $recipientPhone, $response['body'] ?? '');
        } catch (Exception $e) {
            $this->logFailure((string) $eventKey, $recipientPhone, $e->getMessage());
        }
    }

    private function buildMessage(array $order, array $settings, $eventKey, $recipientType = 'customer')
    {
        $templateKey = 'sms_template_' . $eventKey;
        $template = trim((string) ($settings[$templateKey] ?? ''));
        if ($template === '') {
            $template = $this->getDefaultTemplate($eventKey);
        }

        if ($template === '') {
            return '';
        }

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
            '{bank_transfer_details}' => trim((string) ($settings['bank_transfer_details'] ?? ''))
        ];

        if ($recipientType === 'owner') {
            $placeholders['{customer_name}'] = (string) ($order['customer_name'] ?? 'Customer');
        }

        $message = strtr($template, $placeholders);
        if (
            $recipientType === 'customer'
            && $eventKey === 'order_placed'
            && strtolower((string) ($order['payment_method'] ?? '')) === 'bank_transfer'
            && trim((string) ($settings['bank_transfer_details'] ?? '')) !== ''
        ) {
            $message .= ' Bank details: ' . trim((string) $settings['bank_transfer_details']);
        }
        $message = preg_replace('/\s+/', ' ', trim((string) $message));

        if (function_exists('mb_substr')) {
            return mb_substr($message, 0, 621);
        }

        return substr($message, 0, 621);
    }

    private function normalizePhone($phone)
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);
        if (strpos($phone, '+94') === 0) {
            $digits = substr($phone, 3);
            return ctype_digit($digits) ? '+94' . $digits : '';
        }

        if (strpos($phone, '94') === 0) {
            $digits = substr($phone, 2);
            return ctype_digit($digits) ? '+94' . $digits : '';
        }

        if (strpos($phone, '0') === 0) {
            $digits = substr($phone, 1);
            return ctype_digit($digits) ? '+94' . $digits : '';
        }

        return '';
    }

    private function logSuccess($eventKey, $recipientPhone, $responseBody)
    {
        $this->writeLog([
            'time' => date('c'),
            'event' => $eventKey,
            'recipient' => $recipientPhone,
            'status' => 'sent',
            'response' => substr((string) $responseBody, 0, 500)
        ]);
    }

    private function logFailure($eventKey, $recipientPhone, $message)
    {
        $this->writeLog([
            'time' => date('c'),
            'event' => $eventKey,
            'recipient' => $recipientPhone,
            'status' => 'failed',
            'message' => $message
        ]);
    }

    private function writeLog(array $entry)
    {
        $logDir = ROOT_PATH . 'storage/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . 'sms.log',
            json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
