<?php

use App\Lib\Router;
use App\Utils\Response;
use App\Modules\Home\HomeHandler;
use App\Modules\Search\SearchHandler;
use App\Modules\Servers\ServersHandler;
use App\Modules\Stream\StreamHandler;

function setupRoutes(Router $router): void
{
    // Root route - Landing page
    $router->get('/', function() {
        $html = file_get_contents(__DIR__ . '/../Views/index.html');
        Response::html($html);
    });

    // Ping route
    $router->get('/ping', function() {
        Response::text('pong');
    });

    // API v1 routes
    $prefix = '/api/v1';

    // Home
    $router->get($prefix . '/home', function() {
        return HomeHandler::handle();
    });

    // Search
    $router->get($prefix . '/search', function($params, $query) {
        $keyword = $query['keyword'] ?? '';
        $page = (int)($query['page'] ?? 1);
        
        if (empty($keyword)) {
            Response::fail('Keyword parameter is required', 400);
        }
        
        return SearchHandler::handle($keyword, $page);
    });

    // Search suggestions
    $router->get($prefix . '/search/suggestion', function($params, $query) {
        $keyword = $query['keyword'] ?? '';
        
        if (empty($keyword)) {
            Response::fail('Keyword parameter is required', 400);
        }
        
        return SearchHandler::suggestions($keyword);
    });

    // Servers
    $router->get($prefix . '/servers', function($params, $query) {
        $id = $query['id'] ?? '';
        
        if (empty($id)) {
            Response::fail('ID parameter is required', 400);
        }
        
        return ServersHandler::handle($id);
    });

    // Stream
    $router->get($prefix . '/stream', function($params, $query) {
        $id = $query['id'] ?? '';
        $server = $query['server'] ?? '';
        $type = $query['type'] ?? 'sub';
        
        if (empty($id) || empty($server)) {
            Response::fail('ID and server parameters are required', 400);
        }
        
        if (!in_array($type, ['sub', 'dub', 'raw'])) {
            Response::fail('Type must be sub, dub, or raw', 400);
        }
        
        return StreamHandler::handle($id, $server, $type);
    });

    // Documentation route
    $router->get('/doc', function() {
        $html = file_get_contents(__DIR__ . '/../Views/doc.html');
        Response::html($html);
    });
}
