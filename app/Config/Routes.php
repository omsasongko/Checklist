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

// Authenticated application pages (the `session` filter is configured in Config\Filters).
$routes->get('beranda', 'Dashboard::index', ['as' => 'dashboard']);

$routes->get('aktivitas', 'Activities::index', ['as' => 'activities']);
$routes->get('aktivitas/baru', 'Activities::create', ['as' => 'activities.create']);
$routes->post('aktivitas', 'Activities::store', ['as' => 'activities.store']);
$routes->get('aktivitas/(:num)', 'Activities::show/$1', ['as' => 'activities.show']);
$routes->get('aktivitas/(:num)/ubah', 'Activities::edit/$1', ['as' => 'activities.edit']);
$routes->post('aktivitas/(:num)/ubah', 'Activities::update/$1', ['as' => 'activities.update']);
$routes->post('aktivitas/(:num)/nonaktifkan', 'Activities::deactivate/$1', ['as' => 'activities.deactivate']);

$routes->get('jadwal', 'ActivityPlans::index', ['as' => 'plans']);
$routes->post('jadwal', 'ActivityPlans::save', ['as' => 'plans.save']);
$routes->post('jadwal/(:num)/status', 'ActivityPlans::toggle/$1', ['as' => 'plans.toggle']);

$routes->get('checklist', 'Checklist::index', ['as' => 'checklist']);
$routes->post('checklist/(:num)/selesai', 'Checklist::complete/$1', ['as' => 'checklist.complete']);
$routes->get('riwayat', 'Checklist::history', ['as' => 'history']);

// JSON API for activities and daily checklist.
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->get('activities', 'Activities::index');
    $routes->get('activities/today', 'Activities::today');
    $routes->get('activities/history', 'Activities::history');
    $routes->post('activities/(:num)/complete', 'Activities::complete/$1');
});
