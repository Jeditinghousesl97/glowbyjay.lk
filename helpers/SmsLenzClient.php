<?php

class SmsLenzClient
{
    public function send(array $settings, $phone, $message)
    {
        $baseUrl = rtrim((string) ($settings['sms_base_url'] ?? 'https://smslenz.lk/api'), '/');
        $endpoint = $baseUrl . '/send-sms';

        $payload = http_build_query([
            'user_id' => trim((string) ($settings['sms_user_id'] ?? '')),
            'api_key' => trim((string) ($settings['sms_api_key'] ?? '')),
            'sender_id' => trim((string) ($settings['sms_sender_id'] ?? '')),
            'contact' => $phone,
            'message' => $message
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $response = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('SMS request failed: ' . $error);
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $payload,
                    'timeout' => 20,
                ],
            ]);
            $response = @file_get_contents($endpoint, false, $context);
            $statusCode = 200;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
                $statusCode = (int) $match[1];
            }

            if ($response === false) {
                throw new Exception('SMS request failed.');
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new Exception('SMS gateway returned HTTP ' . $statusCode . ': ' . substr((string) $response, 0, 300));
        }

        return [
            'status_code' => $statusCode,
            'body' => (string) $response
        ];
    }
}
