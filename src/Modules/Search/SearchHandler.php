<?php

namespace App\Modules\Search;

use App\Services\HttpClient;
use App\Utils\HtmlParser;
use App\Utils\CommonObjects;
use Symfony\Component\DomCrawler\Crawler;

class SearchHandler
{
    public static function handle(string $keyword, int $page = 1): array
    {
        $html = HttpClient::fetchHtml("/search?keyword=" . urlencode($keyword) . "&page=$page");
        return self::extract($html, $keyword, $page);
    }

    public static function suggestions(string $keyword): array
    {
        $html = HttpClient::fetchHtml("/search/suggest?keyword=" . urlencode($keyword));
        return self::extractSuggestions($html);
    }

    private static function extract(string $html, string $keyword, int $page): array
    {
        $parser = new HtmlParser($html);
        $results = [];

        $parser->each('.film_list-wrap .flw-item', function(Crawler $el) use (&$results) {
            $obj = CommonObjects::animeListObject();

            // Title and ID
            $titleEl = $el->filter('.film-detail .film-name .dynamic-name');
            if ($titleEl->count() > 0) {
                $obj['title'] = $titleEl->attr('title');
                $obj['alternativeTitle'] = $titleEl->attr('data-jname');
                $href = $titleEl->attr('href');
                $parts = explode('/', $href);
                $obj['id'] = end($parts);
            }

            // Poster
            $poster = $el->filter('.film-poster img');
            if ($poster->count() > 0) {
                $obj['poster'] = $poster->attr('data-src');
            }

            // Type
            $type = $el->filter('.film-detail .fd-infor .fdi-item');
            if ($type->count() > 0) {
                $obj['type'] = trim($type->first()->text());
            }

            // Episodes
            $tick = $el->filter('.film-poster .tick');
            if ($tick->count() > 0) {
                $subEl = $tick->filter('.tick-sub');
                $dubEl = $tick->filter('.tick-dub');
                $epsEl = $tick->filter('.tick-eps');

                $obj['episodes']['sub'] = $subEl->count() > 0 ? HtmlParser::extractNumber($subEl->text()) : 0;
                $obj['episodes']['dub'] = $dubEl->count() > 0 ? HtmlParser::extractNumber($dubEl->text()) : 0;

                if ($epsEl->count() > 0) {
                    $obj['episodes']['eps'] = HtmlParser::extractNumber($epsEl->text());
                } else {
                    $obj['episodes']['eps'] = $obj['episodes']['sub'];
                }
            }

            $results[] = $obj;
        });

        return [
            'keyword' => $keyword,
            'page' => $page,
            'results' => $results
        ];
    }

    private static function extractSuggestions(string $html): array
    {
        $parser = new HtmlParser($html);
        $suggestions = [];

        $parser->each('.nav-item', function(Crawler $el) use (&$suggestions) {
            $link = $el->filter('a');
            if ($link->count() > 0) {
                $href = $link->attr('href');
                $parts = explode('/', $href);
                $id = end($parts);

                $title = $link->filter('.film-name')->count() > 0 
                    ? trim($link->filter('.film-name')->text()) 
                    : null;

                $poster = $link->filter('.film-poster img')->count() > 0
                    ? $link->filter('.film-poster img')->attr('src')
                    : null;

                $suggestions[] = [
                    'id' => $id,
                    'title' => $title,
                    'poster' => $poster,
                    'alternativeTitle' => $link->filter('.film-name')->count() > 0 
                        ? $link->filter('.film-name')->attr('data-jname')
                        : null
                ];
            }
        });

        return $suggestions;
    }
}
