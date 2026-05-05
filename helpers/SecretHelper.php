<?php

class SecretHelper
{
    private const PREFIX = 'enc:';

    public static function encrypt($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        if (strpos($value, self::PREFIX) === 0) {
            return $value;
        }

        $key = self::key();
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) {
            return $value;
        }

        return self::PREFIX . base64_encode($iv . $cipherText);
    }

    public static function decrypt($value)
    {
        $value = (string) $value;
        if ($value === '' || strpos($value, self::PREFIX) !== 0) {
            return $value;
        }

        $decoded = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($decoded === false) {
            return '';
        }

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);
        $plainText = openssl_decrypt($cipherText, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv);

        return $plainText === false ? '' : $plainText;
    }

    private static function key()
    {
        $envKey = '';
        if (function_exists('app_config_value')) {
            $envKey = (string) app_config_value('APP_SECRET', '');
        }

        if ($envKey === '') {
            $envKey = (string) getenv('APP_SECRET');
        }

        if (!empty($envKey)) {
            return hash('sha256', $envKey, true);
        }

        $material = implode('|', [
            defined('DB_HOST') ? DB_HOST : '',
            defined('DB_NAME') ? DB_NAME : '',
            defined('DB_USER') ? DB_USER : '',
            defined('DB_PASS') ? DB_PASS : '',
            defined('BASE_URL') ? BASE_URL : '',
            (string) ($_SERVER['SERVER_NAME'] ?? ''),
            defined('ROOT_PATH') ? ROOT_PATH : __DIR__
        ]);

        return hash('sha256', $material, true);
    }
}
