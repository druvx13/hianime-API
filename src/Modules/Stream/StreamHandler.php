<?php

namespace App\Modules\Stream;

use App\Modules\Servers\ServersHandler;
use App\Utils\ValidationError;
use App\Utils\NotFoundError;

class StreamHandler
{
    private const MEGAPLAY = 'https://megaplay.buzz/stream/s-2/';
    private const VIDWISH = 'https://vidwish.live/stream/s-2/';
    private const REFERER = 'https://megacloud.tv';

    public static function handle(string $id, string $server, string $type): array
    {
        // Get available servers
        $servers = ServersHandler::handle($id);

        // Find selected server
        $selectedServer = null;
        foreach ($servers[$type] ?? [] as $s) {
            if ($s['name'] === $server) {
                $selectedServer = $s;
                break;
            }
        }

        if (!$selectedServer) {
            throw new ValidationError('Invalid or server not found', ['server' => $server]);
        }

        // Check if it's an embedded stream
        if (self::isEmbeddedStream($selectedServer)) {
            return self::buildEmbeddedStream($selectedServer, $id);
        }

        // Parse with MegaCloud
        $stream = MegacloudParser::parse($selectedServer, $id);
        
        if (!$stream) {
            throw new NotFoundError('Something went wrong while decryption');
        }

        // Check if we need subtitle fallback
        if (self::needsSubFallback($selectedServer, $stream)) {
            self::attachSubtitlesFromSub($stream, $selectedServer, $id, $servers);
        }

        $stream['referer'] = self::REFERER;
        return $stream;
    }

    private static function isEmbeddedStream(array $server): bool
    {
        return $server['name'] === 'megaplay' || $server['name'] === 'vidwish';
    }

    private static function buildEmbeddedStream(array $server, string $id): array
    {
        $parts = explode('ep=', $id);
        $episodeId = end($parts);

        $endPath = "$episodeId/{$server['type']}";
        $endUrl = $server['name'] === 'megaplay' 
            ? self::MEGAPLAY . $endPath 
            : self::VIDWISH . $endPath;

        return [
            'streamingLink' => $endUrl,
            'servers' => $server['name'],
        ];
    }

    private static function needsSubFallback(array $server, array $stream): bool
    {
        if ($server['type'] !== 'dub') {
            return false;
        }

        $captions = array_filter(
            $stream['tracks'] ?? [],
            fn($t) => ($t['kind'] ?? '') === 'captions' || ($t['kind'] ?? '') === 'subtitles'
        );

        return count($captions) === 0;
    }

    private static function attachSubtitlesFromSub(array &$stream, array $server, string $id, array $allServers): void
    {
        try {
            $subServer = null;
            foreach ($allServers['sub'] ?? [] as $s) {
                if ($s['name'] === $server['name'] || $s['index'] === $server['index']) {
                    $subServer = $s;
                    break;
                }
            }

            if (!$subServer || empty($subServer['id'])) {
                return;
            }

            $subStream = MegacloudParser::parse($subServer, $id);

            $subtitles = array_filter(
                $subStream['tracks'] ?? [],
                fn($t) => ($t['kind'] ?? '') === 'captions' || ($t['kind'] ?? '') === 'subtitles'
            );

            if (count($subtitles) === 0) {
                return;
            }

            $stream['tracks'] = array_merge($stream['tracks'] ?? [], array_values($subtitles));
        } catch (\Exception $e) {
            // No need to throw error for subtitles
            error_log("Failed to fetch subtitles: " . $e->getMessage());
        }
    }
}
