<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', '');

$routes->group('api', function($routes) {
    $routes->group('auth', function($routes) {
        $routes->post('register', 'Api\Auth\RegisterController::register');
        $routes->post('login', 'Api\Auth\LoginController::login');
        $routes->get('logout', 'Api\Auth\LoginController::logout', ['filter' => 'jwtAuth']);
    });

    // Group route yang dilindungi oleh JWTAuthFilter
    $routes->group('profile', ['filter' => 'jwtAuth'], function($routes) {
        $routes->get('/', 'Api\ProfileController::show'); // GET /api/profile (untuk melihat profil)
        $routes->put('/', 'Api\ProfileController::update'); // PUT /api/profile (untuk update profil penuh)
        $routes->patch('/', 'Api\ProfileController::update'); // PATCH /api/profile (untuk update parsial)
    });

    $routes->group('protected', ['filter' => 'jwtAuth'], function($routes) {
        $routes->get('test', 'Api\ProtectedController::index');
    });
});