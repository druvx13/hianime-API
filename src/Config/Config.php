<?php

namespace App\Config;

class Config
{
    public const BASE_URL = 'https://hianime.to';
    
    public const HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:122.0) Gecko/20100101 Firefox/122.0',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Accept-Encoding' => 'gzip, deflate',
        'Connection' => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1'
    ];

    public const MEGACLOUD_KEY_URL = 'https://raw.githubusercontent.com/ryanwtf88/megacloud-keys/refs/heads/master/key.txt';
    
    public const TIMEOUT = 15; // seconds
    public const MAX_RETRIES = 2;

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    public static function getRateLimitWindow(): int
    {
        return (int) ($_ENV['RATE_LIMIT_WINDOW_MS'] ?? 60000);
    }

    public static function getRateLimitMax(): int
    {
        return (int) ($_ENV['RATE_LIMIT_LIMIT'] ?? 100);
    }

    public static function getAllowedOrigins(): array
    {
        $origin = $_ENV['ORIGIN'] ?? '*';
        return $origin === '*' ? ['*'] : explode(',', $origin);
    }

    public static function isDebug(): bool
    {
        return ($_ENV['DEBUG'] ?? 'false') === 'true';
    }
}
