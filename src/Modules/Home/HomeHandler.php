<?php

namespace App\Modules\Home;

use App\Services\HttpClient;
use App\Utils\RedisConnection;
use App\Utils\ValidationError;

class HomeHandler
{
    public static function handle(): array
    {
        // Try to get from cache
        $cached = RedisConnection::get('home');
        if ($cached !== null) {
            return $cached;
        }

        // Fetch from source
        try {
            $html = HttpClient::fetchHtml('/home');
            $response = HomeExtractor::extract($html);
            
            // Cache for 24 hours
            RedisConnection::set('home', $response, 60 * 60 * 24);
            
            return $response;
        } catch (\Exception $e) {
            throw new ValidationError("Failed to fetch home page: " . $e->getMessage());
        }
    }
}
