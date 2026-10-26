<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Throwable;

abstract class BaseService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    protected function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    protected function isDuplicateKey(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'Duplicate entry')
            || str_contains($message, '1062')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
