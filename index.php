<?php
/**
 * HiAnime API - Main Entry Point
 * 
 * This is a PHP-based REST API that scrapes anime data from hianimez.to
 * 
 * @version 2.0.0
 * @author HiAnime API Team
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Set timezone
date_default_timezone_set('UTC');

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Import App
use App\Lib\App;

// Create and run the application
$app = new App();
$app->run();
