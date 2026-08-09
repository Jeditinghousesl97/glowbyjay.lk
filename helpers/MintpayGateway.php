<?php

class MintpayGateway
{
    public const STAGING_BASE_URL = 'https://stage.mintpay.lk';
    public const PRODUCTION_BASE_URL = 'https://app.mintpay.lk';

    public static function isConfigured(array $settings)
    {
        return !empty($settings['mintpay_enabled'])
            && trim((string) ($settings['mintpay_merchant_id'] ?? '')) !== ''
            && trim((string) ($settings['mintpay_api_token'] ?? '')) !== '';
    }

    public static function baseUrl(array $settings)
    {
        return !empty($settings['mintpay_sandbox'])
            ? self::STAGING_BASE_URL
            : self::PRODUCTION_BASE_URL;
    }

    public static function submissionUrl(array $settings)
    {
        return rtrim(self::baseUrl($settings), '/') . '/api/v2/purchase/merchant-gateway/';
    }

    public static function statusUrl(array $settings, $purchaseId)
    {
        return rtrim(self::baseUrl($settings), '/') . '/api/v2/purchase/merchant-gateway/status-purchase?' . http_build_query([
            'merchant_id' => trim((string) ($settings['mintpay_merchant_id'] ?? '')),
            'purchase_id' => trim((string) $purchaseId)
        ]);
    }

    public static function buildPayload(array $order, array $settings, $successUrl, $failUrl)
    {
        $createdAt = (string) ($order['created_at'] ?? date('Y-m-d H:i:s'));
        $items = [];
        foreach (($order['items'] ?? []) as $item) {
            $items[] = [
                'name' => trim((string) ($item['product_title'] ?? 'Product')),
                'product_id' => (string) ($item['product_id'] ?? ''),
                'sku' => trim((string) ($item['variant_key'] ?? $item['variant_text'] ?? '')),
                'quantity' => max(1, (int) ($item['qty'] ?? 1)),
                'unit_price' => number_format((float) ($item['unit_price'] ?? 0), 4, '.', ''),
                'created_date' => (string) ($item['created_at'] ?? $createdAt),
                'updated_date' => (string) ($item['updated_at'] ?? $createdAt)
            ];
        }

        return [
            'merchant_id' => trim((string) ($settings['mintpay_merchant_id'] ?? '')),
            'order_id' => (string) ($order['order_number'] ?? $order['id'] ?? ''),
            'total_price' => number_format((float) ($order['total_amount'] ?? 0), 4, '.', ''),
            'channel' => 'ONL',
            'checkout_option' => 'WEB',
            'customer_email' => trim((string) ($order['email'] ?? '')),
            'customer_telephone' => trim((string) ($order['phone'] ?? '')),
            'ip' => trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
            'x_forwarded_for' => trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')),
            'delivery_street' => trim((string) ($order['address'] ?? '')),
            'delivery_region' => trim((string) ($order['district'] ?? $order['city'] ?? '')),
            'delivery_postcode' => trim((string) ($order['postal_code'] ?? '')),
            'cart_created_date' => $createdAt,
            'cart_updated_date' => (string) ($order['updated_at'] ?? $createdAt),
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'products' => $items
        ];
    }

    public static function createPurchase(array $order, array $settings, $successUrl, $failUrl)
    {
        $payload = self::buildPayload($order, $settings, $successUrl, $failUrl);
        $raw = self::request('POST', self::submissionUrl($settings), $settings, $payload);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('Unexpected response from Mintpay order submission API.');
        }

        $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
        $purchaseId = trim((string) ($data['id'] ?? ''));
        $checkoutLink = trim((string) ($data['checkout_link'] ?? ''));
        if ($purchaseId === '' || $checkoutLink === '') {
            $error = $decoded['data'] ?? $decoded['message'] ?? 'Mintpay did not return a checkout link.';
            if (is_array($error)) $error = json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            throw new Exception((string) $error);
        }

        return ['purchase_id' => $purchaseId, 'checkout_link' => $checkoutLink, 'response' => $decoded, 'payload' => $payload];
    }

    public static function fetchStatus(array $settings, $purchaseId)
    {
        $raw = self::request('GET', self::statusUrl($settings, $purchaseId), $settings);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new Exception('Unexpected response from Mintpay payment status API.');
        }
        return $decoded;
    }

    public static function normalizeStatus($status)
    {
        $status = strtoupper(trim((string) $status));
        if ($status === 'APPROVED') return 'paid';
        if ($status === 'REJECTED') return 'failed';
        return 'pending';
    }

    private static function request($method, $url, array $settings, array $payload = null)
    {
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $headers = [
            'Authorization: Token ' . trim((string) ($settings['mintpay_api_token'] ?? '')),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_HTTPHEADER => $headers
            ]);
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response === false) throw new Exception('Mintpay request failed: ' . $error);
            if ($statusCode >= 400) throw new Exception('Mintpay request failed with HTTP ' . $statusCode . '.');
            return (string) $response;
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $body ?? '',
            'timeout' => 25,
            'ignore_errors' => true
        ]]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) throw new Exception('Mintpay request failed.');
        return (string) $response;
    }
}
