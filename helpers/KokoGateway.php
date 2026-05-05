<?php

class KokoGateway
{
    public const PLUGIN_NAME = 'customapi';
    public const PLUGIN_VERSION = '1.0.1';

    public static function isConfigured(array $settings)
    {
        return !empty($settings['koko_enabled'])
            && !empty($settings['koko_merchant_id'])
            && !empty($settings['koko_api_key'])
            && !empty($settings['koko_public_key'])
            && !empty($settings['koko_private_key']);
    }

    public static function checkoutUrl(array $settings)
    {
        return !empty($settings['koko_sandbox'])
            ? 'https://qaapi.paykoko.com/api/merchants/orderCreate'
            : 'https://prodapi.paykoko.com/api/merchants/orderCreate';
    }

    public static function orderViewUrl(array $settings)
    {
        return !empty($settings['koko_sandbox'])
            ? 'https://qaapi.paykoko.com/api/merchants/orderView'
            : 'https://prodapi.paykoko.com/api/merchants/orderView';
    }

    public static function buildPayload(array $order, array $settings, $description, $returnUrl, $cancelUrl, $responseUrl, $pluginName = self::PLUGIN_NAME, $pluginVersion = self::PLUGIN_VERSION)
    {
        $merchantId = trim((string) $settings['koko_merchant_id']);
        $apiKey = trim((string) $settings['koko_api_key']);
        $amount = number_format((float) ($order['total_amount'] ?? 0), 2, '.', '');
        $currency = trim((string) ($order['currency'] ?? 'LKR'));
        $reference = $merchantId . random_int(111, 999) . '-' . ($order['order_number'] ?? $order['id']);
        $firstName = trim((string) ($order['first_name'] ?? 'Customer'));
        $lastName = trim((string) ($order['last_name'] ?? '-'));
        $email = trim((string) ($order['email'] ?? ''));
        $mobile = trim((string) ($order['phone'] ?? ''));
        $orderId = (string) ($order['id'] ?? '');
        $description = self::normalizeDescription($description, 'Order ' . ($order['order_number'] ?? $orderId));

        $dataString = self::buildCreateOrderDataString(
            $merchantId,
            $amount,
            $currency,
            $pluginName,
            $pluginVersion,
            $returnUrl,
            $cancelUrl,
            $orderId,
            $reference,
            $firstName,
            $lastName,
            $email,
            $description,
            $apiKey,
            $responseUrl
        );

        $signatureEncoded = self::sign($dataString, (string) ($settings['koko_private_key'] ?? ''));

        return [
            '_mId' => $merchantId,
            'api_key' => $apiKey,
            '_returnUrl' => $returnUrl,
            '_responseUrl' => $responseUrl,
            '_currency' => $currency,
            '_amount' => $amount,
            '_reference' => $reference,
            '_pluginName' => $pluginName,
            '_pluginVersion' => $pluginVersion,
            '_cancelUrl' => $cancelUrl,
            '_orderId' => $orderId,
            '_firstName' => $firstName,
            '_lastName' => $lastName,
            '_email' => $email,
            '_description' => $description,
            'dataString' => $dataString,
            'signature' => $signatureEncoded,
            '_mobileNo' => $mobile
        ];
    }

    public static function normalizeDescription($description, $fallback = 'Order')
    {
        $description = trim((string) $description);
        if ($description !== '') {
            $description = html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8');
            $description = preg_replace('/\s+/', ' ', $description);
            $description = preg_replace('/[^A-Za-z0-9 .,_\\-()#]/', '', $description);
            $description = trim((string) $description);
        }

        if ($description === '') {
            $description = trim((string) $fallback);
        }

        if ($description === '') {
            $description = 'Order';
        }

        return function_exists('mb_substr')
            ? mb_substr($description, 0, 80, 'UTF-8')
            : substr($description, 0, 80);
    }

    public static function buildOrderViewPayload($orderId, array $settings, $pluginName = self::PLUGIN_NAME, $pluginVersion = self::PLUGIN_VERSION)
    {
        $merchantId = trim((string) $settings['koko_merchant_id']);
        $apiKey = trim((string) $settings['koko_api_key']);
        $orderId = trim((string) $orderId);

        $dataString = $merchantId . $pluginName . $pluginVersion . $orderId . $apiKey;
        $signature = self::sign($dataString, (string) ($settings['koko_private_key'] ?? ''));

        return [
            '_mId' => $merchantId,
            '_pluginName' => $pluginName,
            '_pluginVersion' => $pluginVersion,
            'api_key' => $apiKey,
            '_orderId' => $orderId,
            'signature' => $signature
        ];
    }

    public static function fetchOrderView($orderId, array $settings, $pluginName = self::PLUGIN_NAME, $pluginVersion = self::PLUGIN_VERSION)
    {
        $url = self::orderViewUrl($settings);
        $payload = self::buildOrderViewPayload($orderId, $settings, $pluginName, $pluginVersion);
        $rawResponse = self::postForm($url, $payload);

        if ($rawResponse === '') {
            throw new Exception('Empty response from KOKO order view API.');
        }

        $decoded = json_decode($rawResponse, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        parse_str($rawResponse, $parsed);
        if (!empty($parsed) && is_array($parsed)) {
            return $parsed;
        }

        throw new Exception('Unexpected KOKO order view response.');
    }

    public static function verifyStatusSignature($orderIdRaw, $trnIdRaw, $statusRaw, $signatureParam, $publicKey)
    {
        $signature = base64_decode((string) $signatureParam, true);
        if ($signature === false) {
            return false;
        }

        $publicKeyResource = openssl_pkey_get_public((string) $publicKey);
        if (!$publicKeyResource) {
            return false;
        }

        $dataString = (string) $orderIdRaw . (string) $trnIdRaw . (string) $statusRaw;
        $verified = openssl_verify($dataString, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        return $verified === 1;
    }

    public static function normalizeStatus($status)
    {
        $status = strtoupper(trim((string) $status));

        if (in_array($status, ['SUCCESS', 'APPROVED', 'COMPLETED'], true)) {
            return 'paid';
        }

        if (in_array($status, ['CANCELLED', 'CANCELED'], true)) {
            return 'cancelled';
        }

        if (in_array($status, ['FAILED', 'FAILURE', 'DECLINED', 'ERROR'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    private static function buildCreateOrderDataString($merchantId, $amount, $currency, $pluginName, $pluginVersion, $returnUrl, $cancelUrl, $orderId, $reference, $firstName, $lastName, $email, $description, $apiKey, $responseUrl)
    {
        return (string) $merchantId
            . (string) $amount
            . (string) $currency
            . (string) $pluginName
            . (string) $pluginVersion
            . (string) $returnUrl
            . (string) $cancelUrl
            . (string) $orderId
            . (string) $reference
            . (string) $firstName
            . (string) $lastName
            . (string) $email
            . (string) $description
            . (string) $apiKey
            . (string) $responseUrl;
    }

    private static function postForm($url, array $payload)
    {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ]
            ]);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('KOKO request failed: ' . $error);
            }

            if ($statusCode >= 400) {
                throw new Exception('KOKO request failed with HTTP ' . $statusCode . '.');
            }

            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 20
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception('KOKO request failed.');
        }

        return (string) $response;
    }

    private static function sign($dataString, $privateKey)
    {
        $privateKeyResource = openssl_pkey_get_private((string) $privateKey);
        if (!$privateKeyResource) {
            throw new Exception('Invalid KOKO private key.');
        }

        $signature = '';
        $result = openssl_sign($dataString, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$result) {
            throw new Exception('Unable to sign KOKO request.');
        }

        return base64_encode($signature);
    }
}
