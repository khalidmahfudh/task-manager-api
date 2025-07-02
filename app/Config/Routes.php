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
    });

    // Group route yang dilindungi oleh JWTAuthFilter
    $routes->group('protected', ['filter' => 'jwtAuth'], function($routes) {
        $routes->get('test', 'Api\ProtectedController::index');
    });
});