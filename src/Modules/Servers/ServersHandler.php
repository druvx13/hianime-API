<?php

namespace App\Modules\Servers;

use App\Config\Config;
use App\Services\HttpClient;
use App\Utils\HtmlParser;
use App\Utils\ValidationError;
use Symfony\Component\DomCrawler\Crawler;

class ServersHandler
{
    public static function handle(string $episodeId): array
    {
        try {
            $html = HttpClient::fetchHtml("/ajax/v2/episode/servers?episodeId=$episodeId");
            return self::extract($html);
        } catch (\Exception $e) {
            throw new ValidationError("Failed to fetch servers: " . $e->getMessage());
        }
    }

    private static function extract(string $html): array
    {
        $parser = new HtmlParser($html);
        $servers = [
            'sub' => [],
            'dub' => [],
            'raw' => []
        ];

        // Extract sub servers
        $parser->each('.ps_-block.ps_-block-sub.servers-sub .ps__-list .server-item', function(Crawler $el, $i) use (&$servers) {
            $servers['sub'][] = [
                'id' => $el->attr('data-id'),
                'name' => strtolower(trim($el->text())),
                'type' => 'sub',
                'index' => $i
            ];
        });

        // Extract dub servers
        $parser->each('.ps_-block.ps_-block-sub.servers-dub .ps__-list .server-item', function(Crawler $el, $i) use (&$servers) {
            $servers['dub'][] = [
                'id' => $el->attr('data-id'),
                'name' => strtolower(trim($el->text())),
                'type' => 'dub',
                'index' => $i
            ];
        });

        // Extract raw servers (if they exist)
        $parser->each('.ps_-block.ps_-block-sub.servers-raw .ps__-list .server-item', function(Crawler $el, $i) use (&$servers) {
            $servers['raw'][] = [
                'id' => $el->attr('data-id'),
                'name' => strtolower(trim($el->text())),
                'type' => 'raw',
                'index' => $i
            ];
        });

        return $servers;
    }
}
