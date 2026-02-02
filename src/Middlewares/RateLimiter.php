<?php

namespace App\Middlewares;

use App\Config\Config;
use App\Utils\Response;

class RateLimiter
{
    private function getStorageFile(): string
    {
        $tempDir = sys_get_temp_dir();
        return $tempDir . DIRECTORY_SEPARATOR . 'hianime_rate_limit.json';
    }

    public function handle(): void
    {
        $ip = $this->getClientIp();
        $limit = Config::getRateLimitMax();
        $window = Config::getRateLimitWindow() / 1000; // Convert to seconds

        $data = $this->loadData();
        $now = time();
        
        // Clean old entries
        $data = $this->cleanOldEntries($data, $now, $window);

        // Check rate limit
        if (!isset($data[$ip])) {
            $data[$ip] = [
                'count' => 1,
                'resetTime' => $now + $window
            ];
        } else {
            if ($now > $data[$ip]['resetTime']) {
                // Reset counter
                $data[$ip] = [
                    'count' => 1,
                    'resetTime' => $now + $window
                ];
            } else {
                $data[$ip]['count']++;
                
                if ($data[$ip]['count'] > $limit) {
                    // Set rate limit headers
                    header('RateLimit-Limit: ' . $limit);
                    header('RateLimit-Remaining: 0');
                    header('RateLimit-Reset: ' . $data[$ip]['resetTime']);
                    
                    Response::fail('Too many requests', 429);
                }
            }
        }

        // Set rate limit headers
        header('RateLimit-Limit: ' . $limit);
        header('RateLimit-Remaining: ' . max(0, $limit - $data[$ip]['count']));
        header('RateLimit-Reset: ' . $data[$ip]['resetTime']);

        $this->saveData($data);
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function loadData(): array
    {
        $storageFile = $this->getStorageFile();
        if (file_exists($storageFile)) {
            $content = file_get_contents($storageFile);
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    private function saveData(array $data): void
    {
        $storageFile = $this->getStorageFile();
        file_put_contents($storageFile, json_encode($data));
    }

    private function cleanOldEntries(array $data, int $now, int $window): array
    {
        return array_filter($data, function($entry) use ($now, $window) {
            return isset($entry['resetTime']) && ($entry['resetTime'] > ($now - $window));
        });
    }
}
