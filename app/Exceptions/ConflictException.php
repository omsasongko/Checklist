<?php

namespace App\Exceptions;

class ConflictException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
