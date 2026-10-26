<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base exception for domain errors that can be translated into an HTTP
 * response by controllers and the JSON API.
 */
abstract class AppException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
