<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Shield Authentication Routes (Indonesian UI)
$routes->get('masuk', 'Auth::login', ['as' => 'login']);
$routes->post('masuk', 'Auth::loginProcess', ['filter' => 'auth-rate']);
$routes->get('daftar', 'Auth::register', ['as' => 'register']);
$routes->post('daftar', 'Auth::registerProcess', ['filter' => 'auth-rate']);

// Password recovery is intentionally unavailable until mail delivery is configured.
$routes->get('lupa-kata-sandi', 'Auth::forgotPassword', ['as' => 'forgot-password']);

// Logout is state-changing: POST only, protected by the global CSRF filter.
$routes->post('keluar', 'Auth::logout', ['as' => 'logout']);

// Dashboard (protected by the global `session` filter in Config\Filters)
$routes->get('beranda', 'Dashboard::index', ['as' => 'dashboard']);

// API routes (protected by the global `session` filter - for future phases)
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->get('me/summary', 'Me::summary');
    $routes->get('me/statistics', 'Me::statistics');
});
