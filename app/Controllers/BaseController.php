<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides shared helpers for authenticated web controllers.
 */
abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        helper(['form', 'lifequest']);
    }

    protected function userId(): int
    {
        $user = service('auth')->user();

        if ($user === null) {
            throw new \RuntimeException('Sesi pengguna tidak ditemukan.');
        }

        return (int) $user->id;
    }

    protected function currentUser(): object
    {
        $user = service('auth')->user();

        if ($user === null) {
            throw new \RuntimeException('Sesi pengguna tidak ditemukan.');
        }

        return $user;
    }

    protected function isAjax(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
    }
}
