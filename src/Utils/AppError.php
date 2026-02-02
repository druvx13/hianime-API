<?php

namespace App\Utils;

/**
 * Custom Exception Classes
 */
class AppError extends \Exception
{
    public function __construct(string $message = "Internal server error", int $code = 500, ?array $details = null)
    {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    public ?array $details = null;
}

class ValidationError extends AppError
{
    public function __construct(string $message, ?array $details = null)
    {
        parent::__construct($message, 400, $details);
    }
}

class NotFoundError extends AppError
{
    public function __construct(string $message = "Not found", ?array $details = null)
    {
        parent::__construct($message, 404, $details);
    }
}
