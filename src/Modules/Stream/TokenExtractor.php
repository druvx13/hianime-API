<?php

namespace App\Modules\Stream;

use App\Config\Config;
use App\Services\HttpClient;
use App\Utils\AppError;
use Symfony\Component\DomCrawler\Crawler;

class TokenExtractor
{
    private const MAX_RETRIES = 3;
    private const MIN_TOKEN_LENGTH = 10;

    public static function extract(string $url, int $retry = 0): ?string
    {
        try {
            $html = self::fetchHTML($url);
            $crawler = new Crawler($html);

            // Try multiple extraction methods in priority order
            $token = self::extractFromMeta($crawler) 
                ?? self::extractFromDataAttr($crawler)
                ?? self::extractFromNonce($crawler)
                ?? self::extractFromWindowStrings($html)
                ?? self::extractFromWindowObjects($html)
                ?? self::extractFromComments($crawler);

            if ($token) {
                return $token;
            }

            throw new AppError("No token found");
        } catch (\Exception $e) {
            error_log("Token extraction error: " . $e->getMessage());
            
            if ($retry < self::MAX_RETRIES - 1) {
                usleep(1000000 * ($retry + 1)); // Backoff
                return self::extract($url, $retry + 1);
            }
            
            return null;
        }
    }

    private static function fetchHTML(string $url): string
    {
        return HttpClient::fetchHtml($url, [
            'Referer' => Config::BASE_URL . '/',
            'Accept' => 'text/html'
        ]);
    }

    private static function extractFromMeta(Crawler $crawler): ?string
    {
        try {
            $meta = $crawler->filter('meta[name="_gg_fb"]');
            if ($meta->count() > 0) {
                return self::validate($meta->attr('content'));
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        return null;
    }

    private static function extractFromDataAttr(Crawler $crawler): ?string
    {
        try {
            $element = $crawler->filter('[data-dpi]');
            if ($element->count() > 0) {
                return self::validate($element->attr('data-dpi'));
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        return null;
    }

    private static function extractFromNonce(Crawler $crawler): ?string
    {
        try {
            $scripts = $crawler->filter('script[nonce]');
            foreach ($scripts as $script) {
                $nonce = $script->getAttribute('nonce');
                if ($nonce) {
                    $validated = self::validate($nonce);
                    if ($validated) {
                        return $validated;
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        return null;
    }

    private static function extractFromWindowStrings(string $html): ?string
    {
        preg_match_all('/window\.\w+\s*=\s*["' . "']" . '([a-zA-Z0-9_-]{10,})["' . "']" . '/m', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $validated = self::validate($match);
                if ($validated) {
                    return $validated;
                }
            }
        }
        
        return null;
    }

    private static function extractFromWindowObjects(string $html): ?string
    {
        preg_match_all('/window\.\w+\s*=\s*(\{[^}]+\});/m', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                try {
                    // Try to extract strings from the object
                    preg_match_all('/["' . "']" . '([a-zA-Z0-9_-]{5,})["' . "']" . '/', $match, $stringMatches);
                    
                    if (!empty($stringMatches[1])) {
                        $joined = implode('', $stringMatches[1]);
                        $validated = self::validate($joined, 20);
                        if ($validated) {
                            return $validated;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        return null;
    }

    private static function extractFromComments(Crawler $crawler): ?string
    {
        // This is harder to do with Symfony DomCrawler, skip for now
        return null;
    }

    private static function validate(?string $value, int $min = self::MIN_TOKEN_LENGTH): ?string
    {
        if ($value && is_string($value) && strlen($value) >= $min) {
            return $value;
        }
        return null;
    }
}
