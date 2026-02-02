<?php

namespace App\Utils;

/**
 * HTTP Response Helper Functions
 */
class Response
{
    /**
     * Send success JSON response
     */
    public static function success(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send error JSON response
     */
    public static function fail(string $message = 'Internal server error', int $code = 500, ?array $details = null): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content): void
    {
        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    /**
     * Send plain text response
     */
    public static function text(string $content, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $content;
        exit;
    }

    /**
     * Send JSON response (custom)
     */
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
