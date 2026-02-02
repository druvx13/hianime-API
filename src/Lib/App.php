<?php

namespace App\Lib;

use App\Config\Config;
use App\Utils\Response;
use App\Utils\AppError;
use App\Middlewares\CorsMiddleware;
use App\Middlewares\RateLimiter;

class App
{
    private Router $router;
    private array $middlewares = [];

    public function __construct()
    {
        $this->router = new Router();
        $this->setupMiddlewares();
        $this->setupRoutes();
    }

    private function setupMiddlewares(): void
    {
        $this->middlewares[] = new CorsMiddleware();
        $this->middlewares[] = new RateLimiter();
    }

    private function setupRoutes(): void
    {
        // Import routes
        require_once __DIR__ . '/../Routes/api.php';
        setupRoutes($this->router);
    }

    public function run(): void
    {
        try {
            // Run middlewares
            foreach ($this->middlewares as $middleware) {
                $middleware->handle();
            }

            // Get request method and URI
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

            // Handle OPTIONS request for CORS
            if ($method === 'OPTIONS') {
                http_response_code(200);
                exit;
            }

            // Route the request
            $this->router->dispatch($method, $uri);
            
        } catch (AppError $e) {
            Response::fail($e->getMessage(), $e->getCode(), $e->details);
        } catch (\Exception $e) {
            if (Config::isDebug()) {
                Response::fail($e->getMessage() . "\n" . $e->getTraceAsString(), 500);
            } else {
                error_log("Unexpected error: " . $e->getMessage());
                Response::fail('Internal server error', 500);
            }
        }
    }
}
