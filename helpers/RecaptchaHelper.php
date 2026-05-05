<?php

class RecaptchaHelper
{
    public static function isEnabled(array $settings)
    {
        return !empty($settings['recaptcha_v3_enabled'])
            && trim((string) ($settings['recaptcha_v3_site_key'] ?? '')) !== ''
            && trim((string) ($settings['recaptcha_v3_secret_key'] ?? '')) !== '';
    }

    public static function siteKey(array $settings)
    {
        return trim((string) ($settings['recaptcha_v3_site_key'] ?? ''));
    }

    public static function minScore(array $settings)
    {
        $score = (float) ($settings['recaptcha_v3_min_score'] ?? 0.5);
        if ($score < 0.1) {
            return 0.1;
        }
        if ($score > 0.9) {
            return 0.9;
        }
        return $score;
    }

    public static function shouldProtectAdminLogin(array $settings)
    {
        return self::isEnabled($settings) && !empty($settings['recaptcha_v3_admin_login']);
    }

    public static function shouldProtectCheckout(array $settings)
    {
        return self::isEnabled($settings) && !empty($settings['recaptcha_v3_checkout']);
    }

    public static function verifyToken(array $settings, $token, $expectedAction = '')
    {
        if (!self::isEnabled($settings)) {
            return ['ok' => true, 'reason' => 'disabled'];
        }

        $token = trim((string) $token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'missing_token'];
        }

        $secret = trim((string) ($settings['recaptcha_v3_secret_key'] ?? ''));
        if ($secret === '') {
            return ['ok' => false, 'reason' => 'missing_secret'];
        }

        $postFields = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        ]);

        $responseBody = '';
        if (function_exists('curl_init')) {
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $responseBody = (string) curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => $postFields,
                    'timeout' => 8
                ]
            ]);
            $responseBody = (string) @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        }

        if ($responseBody === '') {
            return ['ok' => false, 'reason' => 'verification_unavailable'];
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded) || empty($decoded['success'])) {
            return ['ok' => false, 'reason' => 'verification_failed', 'response' => $decoded];
        }

        $score = isset($decoded['score']) ? (float) $decoded['score'] : 0.0;
        if ($score < self::minScore($settings)) {
            return ['ok' => false, 'reason' => 'low_score', 'score' => $score, 'response' => $decoded];
        }

        if ($expectedAction !== '') {
            $action = trim((string) ($decoded['action'] ?? ''));
            if ($action !== $expectedAction) {
                return ['ok' => false, 'reason' => 'action_mismatch', 'score' => $score, 'response' => $decoded];
            }
        }

        return ['ok' => true, 'score' => $score, 'response' => $decoded];
    }
}
