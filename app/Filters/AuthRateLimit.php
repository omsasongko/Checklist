<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication rate limiter.
 *
 * Uses CodeIgniter's Throttler service (the same mechanism behind Shield's
 * AuthRates filter) to limit authentication form submissions per IP address.
 * The response message is localized for the Indonesian interface.
 */
class AuthRateLimit implements FilterInterface
{
    /**
     * Maximum authentication submissions allowed per window.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Throttling window in seconds.
     */
    private const WINDOW_SECONDS = 60;

    /**
     * @param array|null $arguments
     *
     * @return ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $throttler = service('throttler');
        $key       = 'auth_rate_' . md5($request->getIPAddress());

        if ($throttler->check($key, self::MAX_ATTEMPTS, self::WINDOW_SECONDS, 1) === false) {
            $retryAfter = (string) $throttler->getTokenTime();

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', $retryAfter)
                ->setBody(
                    'Terlalu banyak percobaan autentikasi. Silakan coba lagi dalam '
                    . $retryAfter . ' detik.',
                );
        }
    }

    /**
     * @param array|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing required.
    }
}
