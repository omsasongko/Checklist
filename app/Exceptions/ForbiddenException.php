<?php

namespace App\Exceptions;

class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Anda tidak memiliki akses ke data ini.')
    {
        parent::__construct($message, 403);
    }
}
