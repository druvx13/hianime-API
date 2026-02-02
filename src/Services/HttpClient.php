<?php

namespace App\Services;

use App\Config\Config;
use App\Utils\AppError;

class HttpClient
{
    private static ?string $cachedKey = null;
    private static int $keyLastFetched = 0;
    private const KEY_CACHE_DURATION = 3600; // 1 hour in seconds

    /**
     * Fetch URL and return HTML content
     */
    public static function fetchHtml(string $endpoint, array $headers = []): string
    {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : Config::BASE_URL . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => Config::TIMEOUT,
            CURLOPT_HTTPHEADER => self::buildHeaders($headers),
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new AppError("HTTP request failed: $error", 500);
        }

        if ($httpCode >= 400) {
            throw new AppError("HTTP $httpCode", $httpCode);
        }

        return $response;
    }

    /**
     * Fetch URL and return JSON data
     */
    public static function fetchJson(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => Config::TIMEOUT,
            CURLOPT_HTTPHEADER => self::buildHeaders($headers),
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new AppError("HTTP request failed: $error", 500);
        }

        if ($httpCode >= 400) {
            throw new AppError("HTTP $httpCode", $httpCode);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AppError("JSON decode error: " . json_last_error_msg(), 500);
        }

        return $data;
    }

    /**
     * Get decryption key for MegaCloud (with caching)
     */
    public static function getDecryptionKey(): string
    {
        $now = time();

        if (self::$cachedKey && ($now - self::$keyLastFetched) < self::KEY_CACHE_DURATION) {
            return self::$cachedKey;
        }

        try {
            $ch = curl_init(Config::MEGACLOUD_KEY_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response !== false) {
                self::$cachedKey = trim($response);
                self::$keyLastFetched = $now;
                return self::$cachedKey;
            }
        } catch (\Exception $e) {
            // If cached key exists, use it even if refresh failed
            if (self::$cachedKey) {
                return self::$cachedKey;
            }
            throw new AppError("Failed to fetch decryption key", 500);
        }

        throw new AppError("Failed to fetch decryption key", 500);
    }

    /**
     * Build HTTP headers array
     */
    private static function buildHeaders(array $customHeaders = []): array
    {
        $headers = array_merge(Config::HEADERS, $customHeaders);
        $formatted = [];
        
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        
        return $formatted;
    }
}
