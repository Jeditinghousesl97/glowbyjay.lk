<?php

require_once ROOT_PATH . 'helpers/CloudflareR2Helper.php';
require_once ROOT_PATH . 'config/db.php';

class ImageHelper
{
    private const DERIVED_DIR = 'assets/uploads/derived/';
    private const ORIGINAL_DIR = 'assets/uploads/';
    private const QUALITY_WEBP = 82;
    private const QUALITY_AVIF = 52;
    private const QUALITY_JPEG = 84;
    private static $localOptimizationEnabled = null;

    public static function uploadUrl($filename, $fallback = '')
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return $fallback;
        }

        $filename = basename($filename);
        $relativePath = self::ORIGINAL_DIR . $filename;
        $absolutePath = ROOT_PATH . $relativePath;
        if (is_file($absolutePath)) {
            return BASE_URL . $relativePath;
        }

        if (CloudflareR2Helper::isEnabled()) {
            $remoteUrl = CloudflareR2Helper::publicUrl($filename);
            if ($remoteUrl !== '') {
                return $remoteUrl;
            }
        }

        return $fallback;
    }

    public static function settingsImageUrl($url, $fallback = '')
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $url)) {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $filename = basename($path);
            if ($filename !== '' && self::isPortableImage($filename)) {
                return self::uploadUrl($filename, $url);
            }

            return $url;
        }

        if (strpos($url, BASE_URL) === 0) {
            $relativePath = ltrim(substr($url, strlen(BASE_URL)), '/');
            $filename = basename($relativePath);
            if ($filename !== '' && self::isPortableImage($filename)) {
                return self::uploadUrl($filename, $url);
            }

            return is_file(ROOT_PATH . $relativePath) ? $url : $fallback;
        }

        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) {
            return $fallback;
        }

        $relativePath = ltrim(str_replace('/Ecom-CMS/', '', $parsed), '/');
        $filename = basename($relativePath);
        if ($filename !== '' && self::isPortableImage($filename)) {
            return self::uploadUrl($filename, BASE_URL . $relativePath);
        }

        return is_file(ROOT_PATH . $relativePath) ? BASE_URL . $relativePath : $fallback;
    }

    public static function storedAssetUrl($filename, $fallback = '')
    {
        return self::uploadUrl($filename, $fallback);
    }

    public static function attrs(array $attributes)
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if ($value === true) {
                $parts[] = htmlspecialchars((string) $key, ENT_QUOTES);
                continue;
            }

            $parts[] = htmlspecialchars((string) $key, ENT_QUOTES) . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
        }

        return implode(' ', $parts);
    }

    public static function storeUploadedFile(array $file, $prefix = 'img')
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return '';
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $originalName = (string) ($file['name'] ?? 'image');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return '';
        }

        $baseName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($originalName));
        $contentType = (string) ($file['type'] ?? '');

        if (CloudflareR2Helper::isEnabled()) {
            if (CloudflareR2Helper::uploadTmpFile((string) $file['tmp_name'], $baseName, $contentType)) {
                return $baseName;
            }
        }

        self::ensureDirectory(ROOT_PATH . self::ORIGINAL_DIR);
        if (self::localOptimizationEnabled()) {
            self::ensureDirectory(ROOT_PATH . self::DERIVED_DIR);
        }

        $targetPath = ROOT_PATH . self::ORIGINAL_DIR . $baseName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '';
        }

        if (self::localOptimizationEnabled()) {
            self::generateDerivativeSet($baseName);
        }
        return $baseName;
    }

    public static function storeUploadedArrayFile(array $files, $index, $prefix = 'img')
    {
        if (!isset($files['name'][$index])) {
            return '';
        }

        $file = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0
        ];

        return self::storeUploadedFile($file, $prefix);
    }

    public static function getCloudflareMigratableUploads()
    {
        $originalDir = ROOT_PATH . self::ORIGINAL_DIR;
        if (!is_dir($originalDir)) {
            return [];
        }

        $entries = scandir($originalDir) ?: [];
        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $absolutePath = $originalDir . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }

            if (!self::isPortableImage($entry)) {
                continue;
            }

            $files[] = $entry;
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    public static function migrateExistingUploadsToCloudflareBatch($limit = 25, $offset = 0, $deleteLocalAfterUpload = false)
    {
        $files = self::getCloudflareMigratableUploads();
        $total = count($files);
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        if (!CloudflareR2Helper::isEnabled()) {
            return [
                'scanned' => 0,
                'uploaded' => 0,
                'deleted_local' => 0,
                'failed' => 0,
                'files' => [],
                'offset' => $offset,
                'next_offset' => $offset,
                'limit' => $limit,
                'total' => $total,
                'complete' => true,
                'message' => 'Cloudflare image delivery must be enabled and configured before migration can run.'
            ];
        }

        $batchFiles = array_slice($files, $offset, $limit);
        $result = [
            'scanned' => count($batchFiles),
            'uploaded' => 0,
            'deleted_local' => 0,
            'failed' => 0,
            'files' => [],
            'offset' => $offset,
            'next_offset' => min($offset + count($batchFiles), $total),
            'limit' => $limit,
            'total' => $total,
            'complete' => ($offset + count($batchFiles)) >= $total,
            'message' => ''
        ];

        foreach ($batchFiles as $entry) {
            $absolutePath = ROOT_PATH . self::ORIGINAL_DIR . $entry;
            if (!is_file($absolutePath)) {
                $result['failed']++;
                continue;
            }

            $uploaded = CloudflareR2Helper::uploadLocalFile($absolutePath, $entry);
            if (!$uploaded) {
                $result['failed']++;
                continue;
            }

            $result['uploaded']++;
            if (count($result['files']) < 20) {
                $result['files'][] = $entry;
            }

            if ($deleteLocalAfterUpload) {
                self::deleteLocalImageSetOnly($entry);
                $result['deleted_local']++;
            }
        }

        return $result;
    }

    public static function getCloudflareRestorableMissingUploads()
    {
        $files = self::getReferencedImageFilenames();
        $missing = [];

        foreach ($files as $filename) {
            $absolutePath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
            if (!is_file($absolutePath)) {
                $missing[] = $filename;
            }
        }

        sort($missing, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values(array_unique($missing));
    }

    public static function restoreReferencedUploadsFromCloudflareBatch($limit = 25, $offset = 0)
    {
        $files = self::getCloudflareRestorableMissingUploads();
        $total = count($files);
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        if (!CloudflareR2Helper::hasUploadCredentials()) {
            return [
                'scanned' => 0,
                'restored' => 0,
                'optimized' => 0,
                'failed' => 0,
                'files' => [],
                'offset' => $offset,
                'next_offset' => $offset,
                'limit' => $limit,
                'total' => $total,
                'complete' => true,
                'message' => 'Cloudflare R2 credentials are required to pull images back into local uploads.'
            ];
        }

        $batchFiles = array_slice($files, $offset, $limit);
        $result = [
            'scanned' => count($batchFiles),
            'restored' => 0,
            'optimized' => 0,
            'failed' => 0,
            'files' => [],
            'offset' => $offset,
            'next_offset' => min($offset + count($batchFiles), $total),
            'limit' => $limit,
            'total' => $total,
            'complete' => ($offset + count($batchFiles)) >= $total,
            'message' => ''
        ];

        self::ensureDirectory(ROOT_PATH . self::ORIGINAL_DIR);
        if (self::localOptimizationEnabled()) {
            self::ensureDirectory(ROOT_PATH . self::DERIVED_DIR);
        }

        foreach ($batchFiles as $entry) {
            $absolutePath = ROOT_PATH . self::ORIGINAL_DIR . $entry;
            $restored = CloudflareR2Helper::downloadToLocal($entry, $absolutePath);
            if (!$restored) {
                $result['failed']++;
                continue;
            }

            $result['restored']++;
            if (self::localOptimizationEnabled() && self::isOptimizableImage($entry)) {
                if (self::generateDerivativeSet($entry)) {
                    $result['optimized']++;
                }
            }

            if (count($result['files']) < 20) {
                $result['files'][] = $entry;
            }
        }

        return $result;
    }

    public static function deleteImageSet($filename)
    {
        $filename = basename(trim((string) $filename));
        if ($filename === '') {
            return;
        }

        if (CloudflareR2Helper::isEnabled()) {
            CloudflareR2Helper::deleteByFilename($filename);
        }

        $originalPath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
        if (is_file($originalPath)) {
            @unlink($originalPath);
        }

        $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $derivedDir = ROOT_PATH . self::DERIVED_DIR;
        if (!is_dir($derivedDir)) {
            return;
        }

        foreach (glob($derivedDir . $nameWithoutExtension . '__*') ?: [] as $derivedFile) {
            if (is_file($derivedFile)) {
                @unlink($derivedFile);
            }
        }
    }

    public static function getOptimizableUploads()
    {
        if (CloudflareR2Helper::isEnabled() || !self::localOptimizationEnabled()) {
            return [];
        }

        $originalDir = ROOT_PATH . self::ORIGINAL_DIR;
        if (!is_dir($originalDir)) {
            return [];
        }

        $entries = scandir($originalDir) ?: [];
        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $absolutePath = $originalDir . $entry;
            if (!is_file($absolutePath)) {
                continue;
            }

            if (!self::isOptimizableImage($entry)) {
                continue;
            }

            $files[] = $entry;
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    public static function getUploadOptimizationSummary()
    {
        if (CloudflareR2Helper::isEnabled()) {
            return [
                'total_optimizable' => 0,
                'fully_optimized' => 0,
                'missing_derivatives' => 0,
                'missing_formats' => [],
                'derived_files' => 0
            ];
        }

        if (!self::localOptimizationEnabled()) {
            return [
                'total_optimizable' => 0,
                'fully_optimized' => 0,
                'missing_derivatives' => 0,
                'missing_formats' => [],
                'derived_files' => 0
            ];
        }

        $files = self::getOptimizableUploads();
        $summary = [
            'total_optimizable' => count($files),
            'fully_optimized' => 0,
            'missing_derivatives' => 0,
            'missing_formats' => [],
            'derived_files' => 0
        ];

        $derivedDir = ROOT_PATH . self::DERIVED_DIR;
        if (is_dir($derivedDir)) {
            foreach (scandir($derivedDir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (is_file($derivedDir . $entry)) {
                    $summary['derived_files']++;
                }
            }
        }

        foreach ($files as $file) {
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            $expected = self::expectedDerivedFiles($file);
            $existing = self::derivedFilesFor($file);
            $existingMap = [];

            foreach ($existing as $derivedFile) {
                $existingMap[basename($derivedFile)] = true;
            }

            $hasMissing = false;
            foreach ($expected as $expectedFile) {
                $expectedName = $baseName . '__' . $expectedFile['width'] . '.' . $expectedFile['format'];
                if (empty($existingMap[$expectedName])) {
                    $hasMissing = true;
                    $summary['missing_formats'][$expectedFile['format']] = ($summary['missing_formats'][$expectedFile['format']] ?? 0) + 1;
                }
            }

            if ($hasMissing) {
                $summary['missing_derivatives']++;
            } else {
                $summary['fully_optimized']++;
            }
        }

        ksort($summary['missing_formats']);
        return $summary;
    }

    public static function inspectImageSet($input, $profile = 'product_gallery')
    {
        $input = trim((string) $input);
        if ($input === '') {
            return [
                'input' => '',
                'filename' => '',
                'exists' => false,
                'original_url' => '',
                'derived_existing' => [],
                'derived_missing' => [],
                'delivery' => [
                    'src' => '',
                    'sources' => []
                ]
            ];
        }

        $path = parse_url($input, PHP_URL_PATH);
        $filename = basename($path ?: $input);
        $filename = basename(trim($filename));
        $originalPath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
        $exists = $filename !== '' && is_file($originalPath);

        $delivery = self::imageDelivery($filename, '', $profile);
        $expected = (!$exists || CloudflareR2Helper::isEnabled()) ? [] : self::expectedDerivedFiles($filename);
        $derivedExisting = [];
        $derivedMissing = [];
        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        foreach ($expected as $expectedFile) {
            $relative = self::DERIVED_DIR . $baseName . '__' . $expectedFile['width'] . '.' . $expectedFile['format'];
            $absolute = ROOT_PATH . $relative;
            $item = [
                'format' => $expectedFile['format'],
                'width' => $expectedFile['width'],
                'url' => BASE_URL . $relative
            ];

            if (is_file($absolute)) {
                $derivedExisting[] = $item;
            } else {
                $derivedMissing[] = $item;
            }
        }

        return [
            'input' => $input,
            'filename' => $filename,
            'exists' => $exists,
            'original_url' => $exists ? self::uploadUrl($filename, BASE_URL . self::ORIGINAL_DIR . $filename) : '',
            'derived_existing' => $derivedExisting,
            'derived_missing' => $derivedMissing,
            'delivery' => $delivery
        ];
    }

    public static function optimizeExistingUploadsBatch($force = false, $limit = 25, $offset = 0)
    {
        if (CloudflareR2Helper::isEnabled() || !self::localOptimizationEnabled()) {
            return [
                'scanned' => 0,
                'optimized' => 0,
                'skipped' => 0,
                'failed' => 0,
                'formats' => [],
                'files' => [],
                'offset' => 0,
                'next_offset' => 0,
                'limit' => max(1, (int) $limit),
                'total' => 0,
                'complete' => true
            ];
        }

        self::ensureDirectory(ROOT_PATH . self::DERIVED_DIR);
        $files = self::getOptimizableUploads();
        $total = count($files);
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        $batchFiles = array_slice($files, $offset, $limit);
        $result = [
            'scanned' => count($batchFiles),
            'optimized' => 0,
            'skipped' => 0,
            'failed' => 0,
            'formats' => [],
            'files' => [],
            'offset' => $offset,
            'next_offset' => min($offset + count($batchFiles), $total),
            'limit' => $limit,
            'total' => $total,
            'complete' => ($offset + count($batchFiles)) >= $total
        ];

        foreach ($batchFiles as $entry) {
            $beforeFiles = self::derivedFilesFor($entry);
            $beforeCount = count($beforeFiles);

            if ($force) {
                foreach ($beforeFiles as $derivedFile) {
                    if (is_file($derivedFile)) {
                        @unlink($derivedFile);
                    }
                }
            }

            $generated = self::generateDerivativeSet($entry);
            $afterFiles = self::derivedFilesFor($entry);
            $afterCount = count($afterFiles);

            if ($generated === false || $afterCount === 0) {
                $result['failed']++;
                continue;
            }

            if ($force || $afterCount > $beforeCount) {
                $result['optimized']++;
                foreach ($afterFiles as $afterFile) {
                    $ext = strtolower((string) pathinfo($afterFile, PATHINFO_EXTENSION));
                    if ($ext !== '') {
                        $result['formats'][$ext] = ($result['formats'][$ext] ?? 0) + 1;
                    }
                }
                if (count($result['files']) < 20) {
                    $result['files'][] = $entry;
                }
            } else {
                $result['skipped']++;
            }
        }

        ksort($result['formats']);
        return $result;
    }

    public static function imageDelivery($filename, $fallback = '', $profile = 'default')
    {
        $fallbackUrl = self::uploadUrl($filename, $fallback);
        $filename = basename(trim((string) $filename));
        if ($filename === '' || $fallbackUrl === $fallback) {
            return [
                'src' => $fallbackUrl,
                'sources' => [],
                'fallback' => $fallbackUrl,
                'img_srcset' => '',
                'img_sizes' => ''
            ];
        }

        $profileConfig = self::profileConfig($profile);
        if (CloudflareR2Helper::isEnabled()) {
            $srcsetParts = [];
            foreach ($profileConfig['widths'] as $width) {
                $srcsetParts[] = CloudflareR2Helper::transformedUrl($fallbackUrl, $width) . ' ' . $width . 'w';
            }

            $defaultWidth = max($profileConfig['widths']);
            return [
                'src' => CloudflareR2Helper::transformedUrl($fallbackUrl, $defaultWidth),
                'sources' => [],
                'fallback' => $fallbackUrl,
                'img_srcset' => implode(', ', $srcsetParts),
                'img_sizes' => $profileConfig['sizes']
            ];
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $sources = [];

        foreach (['avif', 'webp'] as $format) {
            $srcsetParts = [];
            foreach ($profileConfig['widths'] as $width) {
                $derivedRelative = self::DERIVED_DIR . $baseName . '__' . $width . '.' . $format;
                $derivedAbsolute = ROOT_PATH . $derivedRelative;
                if (is_file($derivedAbsolute)) {
                    $srcsetParts[] = BASE_URL . $derivedRelative . ' ' . $width . 'w';
                }
            }

            if (!empty($srcsetParts)) {
                $sources[] = [
                    'type' => 'image/' . $format,
                    'srcset' => implode(', ', $srcsetParts),
                    'sizes' => $profileConfig['sizes']
                ];
            }
        }

        return [
            'src' => $fallbackUrl,
            'sources' => $sources,
            'fallback' => $fallbackUrl,
            'img_srcset' => '',
            'img_sizes' => ''
        ];
    }

    public static function renderResponsivePicture($filename, $fallback, array $attributes = [], $profile = 'default')
    {
        $delivery = self::imageDelivery($filename, $fallback, $profile);
        $html = '<picture>';

        foreach ($delivery['sources'] as $source) {
            $html .= '<source ' . self::attrs([
                'type' => $source['type'],
                'srcset' => $source['srcset'],
                'sizes' => $source['sizes']
            ]) . '>';
        }

        $imgAttributes = ['src' => $delivery['src']];
        if (!empty($delivery['img_srcset'])) {
            $imgAttributes['srcset'] = $delivery['img_srcset'];
            $imgAttributes['sizes'] = $delivery['img_sizes'] ?? '';
        }

        $attributes = array_merge($imgAttributes, $attributes);
        $html .= '<img ' . self::attrs($attributes) . '>';
        $html .= '</picture>';

        return $html;
    }

    private static function ensureDirectory($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }

    private static function profileConfig($profile)
    {
        $profiles = [
            'product_card' => ['widths' => [320, 640], 'sizes' => '(max-width: 768px) 45vw, 268px'],
            'category_card' => ['widths' => [240, 480], 'sizes' => '(max-width: 768px) 45vw, 240px'],
            'product_gallery' => ['widths' => [640, 960, 1440], 'sizes' => '(max-width: 768px) 100vw, 50vw'],
            'hero' => ['widths' => [640, 960, 1440], 'sizes' => '100vw'],
            'logo' => ['widths' => [120, 240], 'sizes' => '120px'],
            'admin_thumb' => ['widths' => [160, 320], 'sizes' => '160px'],
            'feedback' => ['widths' => [480, 960], 'sizes' => '(max-width: 768px) 90vw, 480px'],
            'default' => ['widths' => [320, 640, 960], 'sizes' => '100vw']
        ];

        return $profiles[$profile] ?? $profiles['default'];
    }

    public static function regenerateImageSet($filename, $force = false)
    {
        if (CloudflareR2Helper::isEnabled() || !self::localOptimizationEnabled()) {
            return false;
        }

        $filename = basename(trim((string) $filename));
        if ($filename === '' || !self::isOptimizableImage($filename)) {
            return false;
        }

        if ($force) {
            foreach (self::derivedFilesFor($filename) as $derivedFile) {
                if (is_file($derivedFile)) {
                    @unlink($derivedFile);
                }
            }
        }

        return self::generateDerivativeSet($filename);
    }

    private static function generateDerivativeSet($filename)
    {
        if (CloudflareR2Helper::isEnabled() || !self::localOptimizationEnabled()) {
            return false;
        }

        $filename = basename(trim((string) $filename));
        if ($filename === '') {
            return false;
        }

        $sourcePath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
        if (!is_file($sourcePath)) {
            return false;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'gif') {
            return false;
        }

        if (extension_loaded('imagick')) {
            return self::generateWithImagick($sourcePath, pathinfo($filename, PATHINFO_FILENAME));
        }

        if (extension_loaded('gd')) {
            return self::generateWithGd($sourcePath, pathinfo($filename, PATHINFO_FILENAME));
        }

        return false;
    }

    private static function generateWithImagick($sourcePath, $baseName)
    {
        try {
            $image = new Imagick($sourcePath);
            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            }

            $sourceWidth = max(1, (int) $image->getImageWidth());
            $written = 0;
            foreach ([160, 240, 320, 480, 640, 960, 1440] as $width) {
                $targetWidth = min($width, $sourceWidth);
                foreach (['webp', 'avif'] as $format) {
                    $variantPath = ROOT_PATH . self::DERIVED_DIR . $baseName . '__' . $targetWidth . '.' . $format;
                    if (is_file($variantPath)) {
                        continue;
                    }

                    $clone = clone $image;
                    $clone->thumbnailImage($targetWidth, 0);
                    $clone->setImageFormat($format);
                    $clone->setImageCompressionQuality($format === 'avif' ? self::QUALITY_AVIF : self::QUALITY_WEBP);
                    if ($clone->writeImage($variantPath)) {
                        $written++;
                    }
                    $clone->clear();
                    $clone->destroy();
                }
            }

            $image->clear();
            $image->destroy();
            return $written > 0 || count(self::derivedFilesFor($baseName)) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function generateWithGd($sourcePath, $baseName)
    {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $mime = strtolower((string) ($imageInfo['mime'] ?? ''));
        $source = self::gdImageFromFile($sourcePath, $mime);
        if (!$source) {
            return false;
        }

        $sourceWidth = max(1, imagesx($source));
        $sourceHeight = max(1, imagesy($source));

        $written = 0;
        foreach ([160, 240, 320, 480, 640, 960, 1440] as $width) {
            $targetWidth = min($width, $sourceWidth);
            $targetHeight = max(1, (int) round(($targetWidth / $sourceWidth) * $sourceHeight));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $webpPath = ROOT_PATH . self::DERIVED_DIR . $baseName . '__' . $targetWidth . '.webp';
            if (!is_file($webpPath) && function_exists('imagewebp') && @imagewebp($canvas, $webpPath, self::QUALITY_WEBP)) {
                $written++;
            }
            $avifPath = ROOT_PATH . self::DERIVED_DIR . $baseName . '__' . $targetWidth . '.avif';
            if (!is_file($avifPath) && function_exists('imageavif') && @imageavif($canvas, $avifPath, self::QUALITY_AVIF)) {
                $written++;
            }

            imagedestroy($canvas);
        }

        imagedestroy($source);
        return $written > 0;
    }

    private static function derivedFilesFor($filename)
    {
        $filename = basename(trim((string) $filename));
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        if ($baseName === '') {
            return [];
        }

        return glob(ROOT_PATH . self::DERIVED_DIR . $baseName . '__*') ?: [];
    }

    private static function expectedDerivedFiles($filename)
    {
        $filename = basename(trim((string) $filename));
        $sourcePath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
        if (!is_file($sourcePath)) {
            return [];
        }

        $dimensions = @getimagesize($sourcePath);
        $sourceWidth = max(1, (int) ($dimensions[0] ?? 0));
        if ($sourceWidth <= 0) {
            return [];
        }

        $formats = [];
        if (extension_loaded('imagick') || function_exists('imageavif')) {
            $formats[] = 'avif';
        }
        if (extension_loaded('imagick') || function_exists('imagewebp')) {
            $formats[] = 'webp';
        }

        $expected = [];
        foreach ([160, 240, 320, 480, 640, 960, 1440] as $width) {
            $targetWidth = min($width, $sourceWidth);
            foreach ($formats as $format) {
                $expected[] = [
                    'width' => $targetWidth,
                    'format' => $format
                ];
            }
        }

        return $expected;
    }

    private static function isOptimizableImage($filename)
    {
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif'], true);
    }

    private static function isPortableImage($filename)
    {
        $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true);
    }

    private static function gdImageFromFile($path, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return @imagecreatefromjpeg($path);
            case 'image/png':
                return @imagecreatefrompng($path);
            case 'image/gif':
                return @imagecreatefromgif($path);
            case 'image/webp':
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
            case 'image/avif':
                return function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : null;
            default:
                return null;
        }
    }

    public static function localOptimizationEnabled()
    {
        if (self::$localOptimizationEnabled !== null) {
            return self::$localOptimizationEnabled;
        }

        require_once ROOT_PATH . 'models/Setting.php';
        $settingModel = new Setting();
        self::$localOptimizationEnabled = $settingModel->get('local_image_optimization_enabled', '1') !== '0';

        return self::$localOptimizationEnabled;
    }

    public static function clearConfigCache()
    {
        self::$localOptimizationEnabled = null;
    }

    private static function getReferencedImageFilenames()
    {
        $db = (new Database())->getConnection();
        if (!$db) {
            return [];
        }

        $files = [];

        $queries = [
            "SELECT main_image AS image_name FROM products WHERE main_image IS NOT NULL AND main_image <> ''",
            "SELECT image_path AS image_name FROM product_images WHERE image_path IS NOT NULL AND image_path <> ''",
            "SELECT image_path AS image_name FROM reviews WHERE image_path IS NOT NULL AND image_path <> ''",
            "SELECT image_path AS image_name FROM size_guides WHERE image_path IS NOT NULL AND image_path <> ''",
            "SELECT image AS image_name FROM categories WHERE image IS NOT NULL AND image <> ''"
        ];

        foreach ($queries as $sql) {
            try {
                $stmt = $db->query($sql);
                foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                    $name = basename((string) ($row['image_name'] ?? ''));
                    if ($name !== '' && self::isPortableImage($name)) {
                        $files[$name] = true;
                    }
                }
            } catch (Throwable $e) {
            }
        }

        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                $key = (string) ($row['setting_key'] ?? '');
                if (!preg_match('/(shop_logo|shop_qr|shop_favicon|hero_slide_[1-3]_(image|mobile_image))/', $key)) {
                    continue;
                }

                $value = trim((string) ($row['setting_value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                $path = (string) parse_url($value, PHP_URL_PATH);
                $name = basename($path ?: $value);
                if ($name !== '' && self::isPortableImage($name)) {
                    $files[$name] = true;
                }
            }
        } catch (Throwable $e) {
        }

        return array_keys($files);
    }

    private static function deleteLocalImageSetOnly($filename)
    {
        $filename = basename(trim((string) $filename));
        if ($filename === '') {
            return;
        }

        $originalPath = ROOT_PATH . self::ORIGINAL_DIR . $filename;
        if (is_file($originalPath)) {
            @unlink($originalPath);
        }

        $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $derivedDir = ROOT_PATH . self::DERIVED_DIR;
        if (!is_dir($derivedDir)) {
            return;
        }

        foreach (glob($derivedDir . $nameWithoutExtension . '__*') ?: [] as $derivedFile) {
            if (is_file($derivedFile)) {
                @unlink($derivedFile);
            }
        }
    }
}
