<?php

namespace App\Middlewares;

use App\Config\Config;

class CorsMiddleware
{
    public function handle(): void
    {
        $origins = Config::getAllowedOrigins();
        
        if (in_array('*', $origins)) {
            header('Access-Control-Allow-Origin: *');
        } else {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $origins)) {
                header("Access-Control-Allow-Origin: $origin");
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Max-Age: 86400');
    }
}
