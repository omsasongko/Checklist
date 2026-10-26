<?php

namespace App\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = 'Data tidak ditemukan.')
    {
        parent::__construct($message, 404);
    }
}
