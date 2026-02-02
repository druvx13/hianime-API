<?php

namespace App\Modules\Stream;

use App\Config\Config;
use App\Services\HttpClient;
use App\Utils\AppError;

class MegacloudParser
{
    private const FALLBACK_PROVIDERS = [
        ['name' => 'megaplay', 'domain' => 'megaplay.buzz'],
        ['name' => 'vidwish', 'domain' => 'vidwish.live'],
    ];
    
    // Token extraction URL parameters
    private const TOKEN_PARAMS = 'k=1&autoPlay=0&oa=0&asi=1';

    public static function parse(array $selectedServer, string $id, int $retry = 0): ?array
    {
        $epID = self::extractEpisodeId($id);

        try {
            // Fetch ajax sources
            $sourcesResponse = self::fetchAjaxSources($selectedServer['id']);
            
            // Get decryption key
            $key = HttpClient::getDecryptionKey();

            // Parse ajax link
            $parsed = self::parseAjaxLink($sourcesResponse['link']);
            $baseUrl = $parsed['baseUrl'];
            $sourceId = $parsed['sourceId'];

            $decrypted = null;
            $rawData = null;
            $usedFallback = false;

            try {
                // Try primary source
                $result = self::decryptPrimarySource($baseUrl, $sourceId, $key);
                $decrypted = $result['sources'];
                $rawData = $result['rawData'];
            } catch (\Exception $e) {
                // Fallback to alternative providers
                $result = self::getFallbackSource($epID, $selectedServer['type'], $selectedServer['name']);
                $decrypted = $result['sources'];
                $rawData = $result['rawData'];
                $usedFallback = true;
            }

            self::validateSources($decrypted);

            return self::buildResult([
                'id' => $id,
                'server' => $selectedServer,
                'file' => $decrypted[0]['file'],
                'rawData' => $rawData,
                'usedFallback' => $usedFallback,
            ]);

        } catch (\Exception $e) {
            error_log("MegaCloud parse error: " . $e->getMessage());
            
            if ($retry < Config::MAX_RETRIES) {
                usleep(2000000 * ($retry + 1)); // Backoff
                return self::parse($selectedServer, $id, $retry + 1);
            }
            
            return null;
        }
    }

    private static function fetchAjaxSources(string $serverId): array
    {
        $data = HttpClient::fetchJson(
            Config::BASE_URL . "/ajax/v2/episode/sources?id=$serverId"
        );

        if (empty($data['link'])) {
            throw new AppError('Missing ajax link');
        }

        return $data;
    }

    private static function parseAjaxLink(string $link): array
    {
        preg_match('/\/([^\/?]+)\?/', $link, $sourceIdMatch);
        preg_match('/^(https?:\/\/[^\/]+(?:\/[^\/]+){3})/', $link, $baseUrlMatch);

        if (empty($sourceIdMatch[1]) || empty($baseUrlMatch[1])) {
            throw new AppError('Invalid ajax link format');
        }

        return [
            'sourceId' => $sourceIdMatch[1],
            'baseUrl' => $baseUrlMatch[1],
        ];
    }

    private static function decryptPrimarySource(string $baseUrl, string $sourceId, string $key): array
    {
        // Extract token
        $token = TokenExtractor::extract("$baseUrl/$sourceId?" . self::TOKEN_PARAMS);
        
        if (!$token) {
            throw new AppError('Token extraction failed');
        }

        // Fetch sources
        $data = HttpClient::fetchJson(
            "$baseUrl/getSources?id=$sourceId&_k=$token",
            [
                'X-Requested-With: XMLHttpRequest',
                "Referer: $baseUrl/$sourceId"
            ]
        );

        $encrypted = $data['sources'] ?? null;
        if (!$encrypted) {
            throw new AppError('Missing encrypted sources');
        }

        // Decrypt if needed
        $sources = is_string($encrypted) ? self::decryptAES($encrypted, $key) : $encrypted;

        return [
            'sources' => $sources,
            'rawData' => $data,
        ];
    }

    private static function decryptAES(string $encrypted, string $key): array
    {
        // Try as string key first
        $decrypted = self::tryDecrypt($encrypted, $key);
        
        // Try as hex key if string failed
        if (!$decrypted) {
            $hexKey = hex2bin($key);
            if ($hexKey !== false) {
                $decrypted = self::tryDecrypt($encrypted, $hexKey);
            }
        }

        if (!$decrypted) {
            throw new AppError('AES decryption failed');
        }

        $decoded = json_decode($decrypted, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AppError('Failed to decode decrypted data');
        }

        return $decoded;
    }

    private static function tryDecrypt(string $encrypted, string $key): ?string
    {
        try {
            // Decode base64
            $data = base64_decode($encrypted);
            if ($data === false) {
                return null;
            }

            // Extract IV and ciphertext
            // CryptoJS format: first 8 bytes are "Salted__", next 8 are salt, rest is ciphertext
            if (substr($data, 0, 8) === 'Salted__') {
                $salt = substr($data, 8, 8);
                $ciphertext = substr($data, 16);
                
                // Derive key and IV using EVP_BytesToKey (MD5)
                $derived = self::evpBytesToKey($key, $salt, 32, 16);
                $derivedKey = $derived['key'];
                $iv = $derived['iv'];
            } else {
                // No salt, use key directly
                $ciphertext = $data;
                $derivedKey = substr(hash('sha256', $key, true), 0, 32);
                $iv = str_repeat("\0", 16);
            }

            // Decrypt
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $derivedKey, OPENSSL_RAW_DATA, $iv);
            
            return $decrypted !== false ? $decrypted : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function evpBytesToKey(string $password, string $salt, int $keyLen, int $ivLen): array
    {
        $m = [];
        $i = 0;
        
        while (strlen(implode('', $m)) < ($keyLen + $ivLen)) {
            $data = ($i === 0) ? $password . $salt : $m[$i - 1] . $password . $salt;
            $m[$i] = md5($data, true);
            $i++;
        }
        
        $ms = implode('', $m);
        
        return [
            'key' => substr($ms, 0, $keyLen),
            'iv' => substr($ms, $keyLen, $ivLen),
        ];
    }

    private static function getFallbackSource(string $epID, string $type, string $serverName): array
    {
        $providers = self::prioritizeFallback($serverName);

        foreach ($providers as $provider) {
            try {
                $html = self::fetchFallbackHTML($provider, $epID, $type);
                $realId = self::extractDataId($html);
                
                if (!$realId) {
                    continue;
                }

                $data = self::fetchFallbackSources($provider, $realId);
                
                if (!empty($data['sources']['file'])) {
                    return [
                        'sources' => [['file' => $data['sources']['file']]],
                        'rawData' => $data,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new AppError('All fallbacks failed');
    }

    private static function prioritizeFallback(string $serverName): array
    {
        $primary = (strtolower($serverName) === 'hd-1') 
            ? self::FALLBACK_PROVIDERS[0] 
            : self::FALLBACK_PROVIDERS[1];

        $others = array_filter(self::FALLBACK_PROVIDERS, fn($p) => $p !== $primary);
        
        return array_merge([$primary], $others);
    }

    private static function fetchFallbackHTML(array $provider, string $epID, string $type): string
    {
        return HttpClient::fetchHtml(
            "https://{$provider['domain']}/stream/s-2/$epID/$type",
            [
                "Referer: https://{$provider['domain']}/",
            ]
        );
    }

    private static function fetchFallbackSources(array $provider, string $id): array
    {
        return HttpClient::fetchJson(
            "https://{$provider['domain']}/stream/getSources?id=$id",
            [
                'X-Requested-With: XMLHttpRequest',
                "Referer: https://{$provider['domain']}/",
            ]
        );
    }

    private static function extractDataId(string $html): ?string
    {
        // Match data-id attribute with single or double quotes
        preg_match('/data-id=["\'](\d+)["\']/', $html, $matches);
        return $matches[1] ?? null;
    }

    private static function extractEpisodeId(string $id): string
    {
        $parts = explode('ep=', $id);
        return end($parts);
    }

    private static function validateSources(?array $sources): void
    {
        if (empty($sources[0]['file'])) {
            throw new AppError('Invalid decrypted sources');
        }
    }

    private static function buildResult(array $params): array
    {
        return [
            'id' => $params['id'],
            'type' => $params['server']['type'],
            'link' => [
                'file' => $params['file'],
                'type' => 'hls',
            ],
            'tracks' => $params['rawData']['tracks'] ?? [],
            'intro' => $params['rawData']['intro'] ?? null,
            'outro' => $params['rawData']['outro'] ?? null,
            'server' => $params['server']['name'],
            'usedFallback' => $params['usedFallback'],
        ];
    }
}
