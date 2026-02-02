<?php

namespace App\Utils;

use Predis\Client;

class RedisConnection
{
    private static ?Client $client = null;
    private static bool $isAvailable = false;

    public static function connect(): array
    {
        $url = env('UPSTASH_REDIS_REST_URL');
        $token = env('UPSTASH_REDIS_REST_TOKEN');

        if (!$url || !$token) {
            return ['exist' => false, 'redis' => null];
        }

        if (self::$client === null) {
            try {
                // Parse the Redis URL
                $parsed = parse_url($url);
                
                self::$client = new Client([
                    'scheme' => $parsed['scheme'] ?? 'tcp',
                    'host' => $parsed['host'] ?? 'localhost',
                    'port' => $parsed['port'] ?? 6379,
                    'password' => $token,
                ]);

                // Test connection
                self::$client->ping();
                self::$isAvailable = true;
            } catch (\Exception $e) {
                error_log("Redis connection failed: " . $e->getMessage());
                self::$isAvailable = false;
                return ['exist' => false, 'redis' => null];
            }
        }

        return [
            'exist' => self::$isAvailable,
            'redis' => self::$isAvailable ? self::$client : null
        ];
    }

    public static function get(string $key)
    {
        $conn = self::connect();
        if (!$conn['exist']) {
            return null;
        }

        try {
            $value = $conn['redis']->get($key);
            return $value ? json_decode($value, true) : null;
        } catch (\Exception $e) {
            error_log("Redis get failed: " . $e->getMessage());
            return null;
        }
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $conn = self::connect();
        if (!$conn['exist']) {
            return false;
        }

        try {
            $encoded = json_encode($value);
            $conn['redis']->setex($key, $ttl, $encoded);
            return true;
        } catch (\Exception $e) {
            error_log("Redis set failed: " . $e->getMessage());
            return false;
        }
    }
}
