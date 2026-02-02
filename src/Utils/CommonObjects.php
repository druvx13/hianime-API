<?php

namespace App\Utils;

class CommonObjects
{
    public static function animeObject(): array
    {
        return [
            'id' => null,
            'title' => null,
            'alternativeTitle' => null,
            'poster' => null,
        ];
    }

    public static function episodeObject(): array
    {
        return [
            'episodes' => [
                'sub' => 0,
                'dub' => 0,
                'eps' => 0,
            ],
        ];
    }

    public static function spotlightObject(): array
    {
        return array_merge(
            self::animeObject(),
            self::episodeObject(),
            [
                'rank' => null,
                'type' => null,
                'quality' => null,
                'duration' => null,
                'aired' => null,
                'synopsis' => null,
            ]
        );
    }

    public static function trendingObject(): array
    {
        return [
            'title' => null,
            'alternativeTitle' => null,
            'rank' => null,
            'poster' => null,
            'id' => null,
        ];
    }

    public static function animeListObject(): array
    {
        return array_merge(
            self::animeObject(),
            self::episodeObject(),
            ['type' => null]
        );
    }

    public static function topTenObject(): array
    {
        return [
            'title' => null,
            'rank' => null,
            'alternativeTitle' => null,
            'id' => null,
            'poster' => null,
            'episodes' => [
                'sub' => 0,
                'dub' => 0,
                'eps' => 0,
            ],
        ];
    }
}
