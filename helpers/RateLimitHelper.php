<?php

class RateLimitHelper
{
    private static function dir()
    {
        $dir = ROOT_PATH . 'storage/tmp/rate_limits';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function filePath($key)
    {
        return self::dir() . '/' . md5($key) . '.json';
    }

    private static function loadAttempts($key)
    {
        $path = self::filePath($key);
        if (!is_file($path)) {
            return [];
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
    }

    private static function saveAttempts($key, array $attempts)
    {
        $path = self::filePath($key);
        @file_put_contents($path, json_encode(array_values($attempts), JSON_UNESCAPED_SLASHES));
    }

    private static function pruneAttempts(array $attempts, $windowSeconds)
    {
        $cutoff = time() - max(1, (int) $windowSeconds);
        return array_values(array_filter($attempts, function ($timestamp) use ($cutoff) {
            return (int) $timestamp >= $cutoff;
        }));
    }

    public static function hit($key, $windowSeconds)
    {
        $attempts = self::pruneAttempts(self::loadAttempts($key), $windowSeconds);
        $attempts[] = time();
        self::saveAttempts($key, $attempts);
        return count($attempts);
    }

    public static function tooManyAttempts($key, $maxAttempts, $windowSeconds)
    {
        $attempts = self::pruneAttempts(self::loadAttempts($key), $windowSeconds);
        self::saveAttempts($key, $attempts);
        return count($attempts) >= max(1, (int) $maxAttempts);
    }

    public static function clear($key)
    {
        $path = self::filePath($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
