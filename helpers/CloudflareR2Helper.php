<?php

require_once ROOT_PATH . 'models/Setting.php';

class CloudflareR2Helper
{
    private const KEY_PREFIX = 'uploads/';
    private static $settingsCache = null;
    private static $clockOffsetSeconds = null;

    public static function isEnabled()
    {
        $settings = self::settings();

        return !empty($settings['cloudflare_images_enabled'])
            && self::hasUploadCredentials()
            && trim((string) ($settings['cloudflare_r2_public_base_url'] ?? '')) !== '';
    }

    public static function hasUploadCredentials()
    {
        $settings = self::settings();

        return trim((string) ($settings['cloudflare_r2_account_id'] ?? '')) !== ''
            && trim((string) ($settings['cloudflare_r2_bucket'] ?? '')) !== ''
            && trim((string) ($settings['cloudflare_r2_access_key_id'] ?? '')) !== ''
            && trim((string) ($settings['cloudflare_r2_secret_access_key'] ?? '')) !== '';
    }

    public static function publicUrl($filename)
    {
        $filename = self::cleanFilename($filename);
        if ($filename === '' || !self::isEnabled()) {
            return '';
        }

        $baseUrl = rtrim((string) (self::settings()['cloudflare_r2_public_base_url'] ?? ''), '/');
        if ($baseUrl === '') {
            return '';
        }

        return $baseUrl . '/' . self::KEY_PREFIX . rawurlencode($filename);
    }

    public static function uploadTmpFile($tmpPath, $filename, $contentType = 'application/octet-stream')
    {
        $filename = self::cleanFilename($filename);
        $tmpPath = (string) $tmpPath;

        if ($filename === '' || $tmpPath === '' || !is_file($tmpPath) || !self::isEnabled()) {
            return false;
        }

        $body = @file_get_contents($tmpPath);
        if ($body === false) {
            return false;
        }

        $response = self::signedRequest(
            'PUT',
            self::bucketUrl(self::objectKey($filename)),
            $body,
            [
                'content-type' => trim((string) $contentType) !== '' ? (string) $contentType : 'application/octet-stream'
            ]
        );

        return !empty($response['ok']);
    }

    public static function uploadLocalFile($absolutePath, $filename)
    {
        $absolutePath = (string) $absolutePath;
        $filename = self::cleanFilename($filename);
        if ($absolutePath === '' || $filename === '' || !is_file($absolutePath)) {
            return false;
        }

        $contentType = function_exists('mime_content_type') ? (string) @mime_content_type($absolutePath) : '';
        if ($contentType === '') {
            $contentType = 'application/octet-stream';
        }

        return self::uploadTmpFile($absolutePath, $filename, $contentType);
    }

    public static function deleteByFilename($filename)
    {
        $filename = self::cleanFilename($filename);
        if ($filename === '' || !self::isEnabled()) {
            return false;
        }

        $response = self::signedRequest('DELETE', self::bucketUrl(self::objectKey($filename)), '');
        return !empty($response['ok']);
    }

    public static function downloadToLocal($filename, $targetPath)
    {
        $filename = self::cleanFilename($filename);
        $targetPath = (string) $targetPath;
        if ($filename === '' || $targetPath === '') {
            return false;
        }

        $body = self::downloadBodyFromPublicUrl($filename);
        if ($body === null && self::hasUploadCredentials()) {
            $response = self::signedRequest('GET', self::bucketUrl(self::objectKey($filename)), '');
            if (!empty($response['ok']) && is_string($response['body']) && $response['body'] !== '') {
                $body = $response['body'];
            }
        }

        if ($body === null || $body === '') {
            return false;
        }

        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        return @file_put_contents($targetPath, $body) !== false;
    }

    public static function transformedUrl($sourceUrl, $width = null, array $extraOptions = [])
    {
        $sourceUrl = trim((string) $sourceUrl);
        if ($sourceUrl === '') {
            return '';
        }

        $options = [];
        if ($width !== null && (int) $width > 0) {
            $options[] = 'width=' . (int) $width;
        }

        $options[] = 'format=auto';
        $options[] = 'fit=scale-down';
        $options[] = 'metadata=none';
        $options[] = 'quality=' . (int) ($extraOptions['quality'] ?? 82);

        return '/cdn-cgi/image/' . implode(',', $options) . '/' . ltrim(str_replace(' ', '%20', $sourceUrl), '/');
    }

    public static function settings()
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $settingModel = new Setting();
        self::$settingsCache = $settingModel->getMultiple([
            'cloudflare_images_enabled',
            'cloudflare_r2_account_id',
            'cloudflare_r2_bucket',
            'cloudflare_r2_access_key_id',
            'cloudflare_r2_secret_access_key',
            'cloudflare_r2_public_base_url'
        ]);

        return self::$settingsCache;
    }

    public static function clearCache()
    {
        self::$settingsCache = null;
    }

    public static function statusSummary()
    {
        $settings = self::settings();
        $enabled = !empty($settings['cloudflare_images_enabled']);
        $hasCredentials = self::hasUploadCredentials();
        $hasPublicBase = trim((string) ($settings['cloudflare_r2_public_base_url'] ?? '')) !== '';

        if (!$enabled) {
            return [
                'state' => 'off',
                'label' => 'Cloudflare Off',
                'message' => 'Cloudflare image delivery is currently disabled. Local storage rules apply.'
            ];
        }

        if (!$hasCredentials || !$hasPublicBase) {
            return [
                'state' => 'misconfigured',
                'label' => 'Needs Setup',
                'message' => 'Cloudflare is turned on, but account credentials or the public image base URL are incomplete.'
            ];
        }

        return [
            'state' => 'ready',
            'label' => 'Ready To Test',
            'message' => 'Cloudflare settings are filled in. Use Test Connection to verify R2 upload/delete access.'
        ];
    }

    public static function testConnection()
    {
        if (!self::hasUploadCredentials()) {
            return [
                'ok' => false,
                'message' => 'Missing Cloudflare R2 credentials.'
            ];
        }

        $testName = '__cf_test_' . time() . '_' . bin2hex(random_bytes(4)) . '.txt';
        $tempPath = tempnam(sys_get_temp_dir(), 'cfimg_');
        if ($tempPath === false) {
            return [
                'ok' => false,
                'message' => 'Could not create a temporary file for testing.'
            ];
        }

        $testBody = 'cloudflare-r2-test:' . date('c');
        file_put_contents($tempPath, $testBody);
        $uploadResponse = self::signedRequest(
            'PUT',
            self::bucketUrl(self::objectKey($testName)),
            $testBody,
            ['content-type' => 'text/plain']
        );
        @unlink($tempPath);

        if (empty($uploadResponse['ok'])) {
            $detailParts = [];
            if (isset($uploadResponse['status'])) {
                $detailParts[] = 'HTTP ' . (int) $uploadResponse['status'];
            }
            if (!empty($uploadResponse['error'])) {
                $detailParts[] = trim((string) $uploadResponse['error']);
            }
            $bodyPreview = trim((string) ($uploadResponse['body'] ?? ''));
            if ($bodyPreview !== '') {
                $bodyPreview = preg_replace('/\s+/', ' ', $bodyPreview);
                $detailParts[] = function_exists('mb_strimwidth')
                    ? mb_strimwidth($bodyPreview, 0, 180, '...')
                    : substr($bodyPreview, 0, 180) . (strlen($bodyPreview) > 180 ? '...' : '');
            }

            return [
                'ok' => false,
                'message' => 'R2 upload test failed. Check Account ID, bucket name, Access Key ID, and Secret Access Key.' . (!empty($detailParts) ? ' Details: ' . implode(' | ', $detailParts) : '')
            ];
        }

        $deleteResponse = self::signedRequest('DELETE', self::bucketUrl(self::objectKey($testName)), '');
        if (empty($deleteResponse['ok'])) {
            $detailParts = [];
            if (isset($deleteResponse['status'])) {
                $detailParts[] = 'HTTP ' . (int) $deleteResponse['status'];
            }
            if (!empty($deleteResponse['error'])) {
                $detailParts[] = trim((string) $deleteResponse['error']);
            }
            $bodyPreview = trim((string) ($deleteResponse['body'] ?? ''));
            if ($bodyPreview !== '') {
                $bodyPreview = preg_replace('/\s+/', ' ', $bodyPreview);
                $detailParts[] = function_exists('mb_strimwidth')
                    ? mb_strimwidth($bodyPreview, 0, 180, '...')
                    : substr($bodyPreview, 0, 180) . (strlen($bodyPreview) > 180 ? '...' : '');
            }

            return [
                'ok' => false,
                'message' => 'R2 upload worked, but delete test failed. Upload/delete permissions may be incomplete.' . (!empty($detailParts) ? ' Details: ' . implode(' | ', $detailParts) : '')
            ];
        }

        return [
            'ok' => true,
            'message' => 'Cloudflare R2 upload and delete test completed successfully.'
        ];
    }

    private static function cleanFilename($filename)
    {
        return basename(trim((string) $filename));
    }

    private static function objectKey($filename)
    {
        return self::KEY_PREFIX . self::cleanFilename($filename);
    }

    private static function bucketUrl($objectKey)
    {
        $settings = self::settings();
        $accountId = trim((string) ($settings['cloudflare_r2_account_id'] ?? ''));
        $bucket = trim((string) ($settings['cloudflare_r2_bucket'] ?? ''));

        return 'https://' . $accountId . '.r2.cloudflarestorage.com/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode($objectKey));
    }

    private static function signedRequest($method, $url, $body = '', array $headers = [])
    {
        $settings = self::settings();
        $accessKey = trim((string) ($settings['cloudflare_r2_access_key_id'] ?? ''));
        $secretKey = trim((string) ($settings['cloudflare_r2_secret_access_key'] ?? ''));

        if ($accessKey === '' || $secretKey === '') {
            return ['ok' => false, 'status' => 0, 'body' => 'Missing Cloudflare R2 credentials'];
        }

        $method = strtoupper((string) $method);
        $timestamp = self::cloudflareTimestamp();
        $date = gmdate('Ymd', self::cloudflareUtcTime());
        $region = 'auto';
        $service = 's3';
        $scope = $date . '/' . $region . '/' . $service . '/aws4_request';
        $payloadHash = hash('sha256', (string) $body);

        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        $path = (string) ($parts['path'] ?? '/');

        $canonicalHeaders = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $timestamp
        ];

        foreach ($headers as $key => $value) {
            $canonicalHeaders[strtolower((string) $key)] = trim((string) $value);
        }

        ksort($canonicalHeaders);

        $canonicalHeaderString = '';
        foreach ($canonicalHeaders as $key => $value) {
            $canonicalHeaderString .= $key . ':' . preg_replace('/\s+/', ' ', $value) . "\n";
        }

        $signedHeaders = implode(';', array_keys($canonicalHeaders));
        $canonicalRequest = implode("\n", [
            $method,
            $path,
            '',
            $canonicalHeaderString,
            $signedHeaders,
            $payloadHash
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $timestamp,
            $scope,
            hash('sha256', $canonicalRequest)
        ]);

        $signingKey = self::signatureKey($secretKey, $date, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $requestHeaders = [
            'Authorization: AWS4-HMAC-SHA256 Credential=' . $accessKey . '/' . $scope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: ' . $timestamp,
            'Host: ' . $host
        ];

        foreach ($headers as $key => $value) {
            $requestHeaders[] = $key . ': ' . trim((string) $value);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $responseBody,
            'error' => $error
        ];
    }

    private static function cloudflareUtcTime()
    {
        return time() + self::clockOffsetSeconds();
    }

    private static function cloudflareTimestamp()
    {
        return gmdate('Ymd\THis\Z', self::cloudflareUtcTime());
    }

    private static function clockOffsetSeconds()
    {
        if (self::$clockOffsetSeconds !== null) {
            return self::$clockOffsetSeconds;
        }

        self::$clockOffsetSeconds = self::fetchRemoteClockOffsetSeconds();
        return self::$clockOffsetSeconds;
    }

    private static function fetchRemoteClockOffsetSeconds()
    {
        $targets = [
            'https://www.cloudflare.com',
            'https://1.1.1.1'
        ];

        foreach ($targets as $target) {
            $dateHeader = self::fetchRemoteDateHeader($target);
            if ($dateHeader === '') {
                continue;
            }

            $remoteTime = strtotime($dateHeader);
            if ($remoteTime === false) {
                continue;
            }

            return $remoteTime - time();
        }

        return 0;
    }

    private static function fetchRemoteDateHeader($url)
    {
        if (!function_exists('curl_init')) {
            return '';
        }

        $capturedDate = '';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $headerLine) use (&$capturedDate) {
            $length = strlen($headerLine);
            if (stripos($headerLine, 'Date:') === 0) {
                $capturedDate = trim(substr($headerLine, 5));
            }
            return $length;
        });
        curl_exec($ch);
        curl_close($ch);

        return $capturedDate;
    }

    private static function signatureKey($secretKey, $date, $region, $service)
    {
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private static function downloadBodyFromPublicUrl($filename)
    {
        $publicUrl = self::publicUrl($filename);
        if ($publicUrl === '') {
            return null;
        }

        $ch = curl_init($publicUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300 && is_string($body) && $body !== '') {
            return $body;
        }

        return null;
    }
}
