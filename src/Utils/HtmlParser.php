<?php

namespace App\Utils;

use Symfony\Component\DomCrawler\Crawler;

class HtmlParser
{
    private Crawler $crawler;

    public function __construct(string $html)
    {
        $this->crawler = new Crawler($html);
    }

    public function filter(string $selector): self
    {
        return new self($this->crawler->filter($selector)->html());
    }

    public function each(string $selector, callable $callback): array
    {
        $results = [];
        $this->crawler->filter($selector)->each(function (Crawler $node, $i) use (&$results, $callback) {
            $results[] = $callback($node, $i);
        });
        return $results;
    }

    public function find(string $selector): ?Crawler
    {
        try {
            $filtered = $this->crawler->filter($selector);
            return $filtered->count() > 0 ? $filtered : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function text(string $selector, string $default = ''): string
    {
        try {
            $filtered = $this->crawler->filter($selector);
            return $filtered->count() > 0 ? trim($filtered->text()) : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public function attr(string $selector, string $attribute, ?string $default = null): ?string
    {
        try {
            $filtered = $this->crawler->filter($selector);
            if ($filtered->count() > 0) {
                return $filtered->attr($attribute);
            }
        } catch (\Exception $e) {
            // Return default
        }
        return $default;
    }

    public function html(): string
    {
        return $this->crawler->html();
    }

    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * Extract number from text
     */
    public static function extractNumber(string $text, int $default = 0): int
    {
        preg_match('/\d+/', $text, $matches);
        return isset($matches[0]) ? (int)$matches[0] : $default;
    }
}
