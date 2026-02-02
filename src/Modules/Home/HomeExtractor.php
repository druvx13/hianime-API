<?php

namespace App\Modules\Home;

use App\Utils\HtmlParser;
use App\Utils\CommonObjects;
use Symfony\Component\DomCrawler\Crawler;

class HomeExtractor
{
    public static function extract(string $html): array
    {
        $parser = new HtmlParser($html);
        
        $response = [
            'spotlight' => [],
            'trending' => [],
            'topAiring' => [],
            'mostPopular' => [],
            'mostFavorite' => [],
            'latestCompleted' => [],
            'latestEpisode' => [],
            'newAdded' => [],
            'topUpcoming' => [],
            'topTen' => [
                'today' => null,
                'week' => null,
                'month' => null,
            ],
            'genres' => [],
        ];

        // Extract spotlight
        $response['spotlight'] = self::extractSpotlight($parser);
        
        // Extract trending
        $response['trending'] = self::extractTrending($parser);
        
        // Extract featured sections
        $response = self::extractFeatured($parser, $response);
        
        // Extract home blocks
        $response = self::extractHomeBlocks($parser, $response);
        
        // Extract top ten
        $response['topTen'] = self::extractTopTen($parser);
        
        // Extract genres
        $response['genres'] = self::extractGenres($parser);

        return $response;
    }

    private static function extractSpotlight(HtmlParser $parser): array
    {
        $spotlight = [];
        
        $parser->each('.deslide-wrap .swiper-wrapper .swiper-slide', function(Crawler $el, $i) use (&$spotlight) {
            $obj = CommonObjects::spotlightObject();
            $obj['rank'] = $i + 1;
            
            // ID
            $href = $el->filter('.desi-buttons a')->first();
            if ($href->count() > 0) {
                $url = $href->attr('href');
                $parts = explode('/', $url);
                $obj['id'] = end($parts);
            }
            
            // Poster
            $poster = $el->filter('.deslide-cover .film-poster-img');
            if ($poster->count() > 0) {
                $obj['poster'] = $poster->attr('data-src');
            }
            
            // Title
            $titles = $el->filter('.desi-head-title');
            if ($titles->count() > 0) {
                $obj['title'] = trim($titles->text());
                $obj['alternativeTitle'] = $titles->attr('data-jname');
            }
            
            // Synopsis
            $synopsis = $el->filter('.desi-description');
            if ($synopsis->count() > 0) {
                $obj['synopsis'] = trim($synopsis->text());
            }
            
            // Details
            $details = $el->filter('.sc-detail');
            if ($details->count() > 0) {
                $items = $details->filter('.scd-item');
                if ($items->count() > 0) {
                    $obj['type'] = trim($items->eq(0)->text());
                }
                if ($items->count() > 1) {
                    $obj['duration'] = trim($items->eq(1)->text());
                }
                
                $mhide = $details->filter('.scd-item.m-hide');
                if ($mhide->count() > 0) {
                    $obj['aired'] = trim($mhide->text());
                }
                
                $quality = $details->filter('.scd-item .quality');
                if ($quality->count() > 0) {
                    $obj['quality'] = trim($quality->text());
                }
                
                // Episodes
                $tick = $details->filter('.tick');
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
            }
            
            $spotlight[] = $obj;
        });
        
        return $spotlight;
    }

    private static function extractTrending(HtmlParser $parser): array
    {
        $trending = [];
        
        $parser->each('#trending-home .swiper-container .swiper-slide', function(Crawler $el, $i) use (&$trending) {
            $obj = CommonObjects::trendingObject();
            $obj['rank'] = $i + 1;
            
            // Title
            $titleEl = $el->filter('.item .film-title');
            if ($titleEl->count() > 0) {
                $obj['title'] = trim($titleEl->text());
                $obj['alternativeTitle'] = $titleEl->attr('data-jname');
            }
            
            // Poster and ID
            $imageEl = $el->filter('.film-poster');
            if ($imageEl->count() > 0) {
                $img = $imageEl->filter('img');
                if ($img->count() > 0) {
                    $obj['poster'] = $img->attr('data-src');
                }
                
                $href = $imageEl->attr('href');
                $parts = explode('/', $href);
                $obj['id'] = end($parts);
            }
            
            $trending[] = $obj;
        });
        
        return $trending;
    }

    private static function extractFeatured(HtmlParser $parser, array $response): array
    {
        $parser->each('#anime-featured .anif-blocks .row .anif-block', function(Crawler $el) use (&$response) {
            $data = [];
            
            $el->filter('.anif-block-ul ul li')->each(function(Crawler $item) use (&$data) {
                $obj = CommonObjects::animeListObject();
                
                // Title and ID
                $titleEl = $item->filter('.film-detail .film-name a');
                if ($titleEl->count() > 0) {
                    $obj['title'] = $titleEl->attr('title');
                    $obj['alternativeTitle'] = $titleEl->attr('data-jname');
                    $href = $titleEl->attr('href');
                    $parts = explode('/', $href);
                    $obj['id'] = end($parts);
                }
                
                // Poster
                $poster = $item->filter('.film-poster .film-poster-img');
                if ($poster->count() > 0) {
                    $obj['poster'] = $poster->attr('data-src');
                }
                
                // Type
                $type = $item->filter('.fd-infor .fdi-item');
                if ($type->count() > 0) {
                    $obj['type'] = trim($type->text());
                }
                
                // Episodes
                $subEl = $item->filter('.fd-infor .tick-sub');
                $dubEl = $item->filter('.fd-infor .tick-dub');
                $epsEl = $item->filter('.fd-infor .tick-eps');
                
                $obj['episodes']['sub'] = $subEl->count() > 0 ? HtmlParser::extractNumber($subEl->text()) : 0;
                $obj['episodes']['dub'] = $dubEl->count() > 0 ? HtmlParser::extractNumber($dubEl->text()) : 0;
                
                if ($epsEl->count() > 0) {
                    $obj['episodes']['eps'] = HtmlParser::extractNumber($epsEl->text());
                } else {
                    $obj['episodes']['eps'] = $obj['episodes']['sub'];
                }
                
                $data[] = $obj;
            });
            
            // Get section name
            $header = $el->filter('.anif-block-header');
            if ($header->count() > 0) {
                $dataType = preg_replace('/\s+/', '', $header->text());
                $normalizedDataType = lcfirst($dataType);
                $response[$normalizedDataType] = $data;
            }
        });
        
        return $response;
    }

    private static function extractHomeBlocks(HtmlParser $parser, array $response): array
    {
        $parser->each('.block_area.block_area_home', function(Crawler $el) use (&$response) {
            $data = [];
            
            $el->filter('.tab-content .film_list-wrap .flw-item')->each(function(Crawler $item) use (&$data) {
                $obj = CommonObjects::animeListObject();
                unset($obj['type']);
                
                // Title and ID
                $titleEl = $item->filter('.film-detail .film-name .dynamic-name');
                if ($titleEl->count() > 0) {
                    $obj['title'] = $titleEl->attr('title');
                    $obj['alternativeTitle'] = $titleEl->attr('data-jname');
                    $href = $titleEl->attr('href');
                    $parts = explode('/', $href);
                    $obj['id'] = end($parts);
                }
                
                // Poster
                $poster = $item->filter('.film-poster img');
                if ($poster->count() > 0) {
                    $obj['poster'] = $poster->attr('data-src');
                }
                
                // Episodes
                $tick = $item->filter('.film-poster .tick');
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
                
                $data[] = $obj;
            });
            
            // Get section name
            $header = $el->filter('.cat-heading');
            if ($header->count() > 0) {
                $dataType = preg_replace('/\s+/', '', $header->text());
                $normalizedDataType = lcfirst($dataType);
                
                // Special case for "newOnHiAnime"
                if ($normalizedDataType === 'newOnHiAnime') {
                    $response['newAdded'] = $data;
                } else {
                    $response[$normalizedDataType] = $data;
                }
            }
        });
        
        return $response;
    }

    private static function extractTopTen(HtmlParser $parser): array
    {
        $extractTopTenBySelector = function($selector) use ($parser) {
            $results = [];
            
            $parser->each(".block_area .cbox $selector ul li", function(Crawler $el, $i) use (&$results) {
                $obj = CommonObjects::topTenObject();
                $obj['rank'] = $i + 1;
                
                // Title and ID
                $titleEl = $el->filter('.film-name a');
                if ($titleEl->count() > 0) {
                    $obj['title'] = trim($titleEl->text());
                    $obj['alternativeTitle'] = $titleEl->attr('data-jname');
                    $href = $titleEl->attr('href');
                    $parts = explode('/', $href);
                    $obj['id'] = array_pop($parts);
                }
                
                // Poster
                $poster = $el->filter('.film-poster img');
                if ($poster->count() > 0) {
                    $obj['poster'] = $poster->attr('data-src');
                }
                
                // Episodes
                $subEl = $el->filter('.tick-item.tick-sub');
                $dubEl = $el->filter('.tick-item.tick-dub');
                $epsEl = $el->filter('.tick-item.tick-eps');
                
                $obj['episodes']['sub'] = $subEl->count() > 0 ? HtmlParser::extractNumber($subEl->text()) : 0;
                $obj['episodes']['dub'] = $dubEl->count() > 0 ? HtmlParser::extractNumber($dubEl->text()) : 0;
                
                if ($epsEl->count() > 0) {
                    $obj['episodes']['eps'] = HtmlParser::extractNumber($epsEl->text());
                } else {
                    $obj['episodes']['eps'] = $obj['episodes']['sub'];
                }
                
                $results[] = $obj;
            });
            
            return $results;
        };
        
        return [
            'today' => $extractTopTenBySelector('#top-viewed-day'),
            'week' => $extractTopTenBySelector('#top-viewed-week'),
            'month' => $extractTopTenBySelector('#top-viewed-month'),
        ];
    }

    private static function extractGenres(HtmlParser $parser): array
    {
        $genres = [];
        
        $parser->each('.sb-genre-list li', function(Crawler $el) use (&$genres) {
            $link = $el->filter('a');
            if ($link->count() > 0) {
                $genre = strtolower($link->attr('title'));
                $genres[] = $genre;
            }
        });
        
        return $genres;
    }
}
