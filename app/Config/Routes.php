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


    // Grup rute yang dilindungi oleh JWTAuthFilter
    $routes->group('/', ['filter' => 'jwtAuth'], function($routes) {

        // Profile API (URL: /api/profile)
        $routes->group('profile', function($routes) {
            $routes->get('/', 'Api\ProfileController::show'); // GET /api/profile (untuk melihat profil)
            $routes->put('/', 'Api\ProfileController::update'); // PUT /api/profile (untuk update profil penuh)
            $routes->patch('/', 'Api\ProfileController::update'); // PATCH /api/profile (untuk update parsial)
        });

        // Tasks API (URL: /api/tasks)
        $routes->get('tasks', 'Api\TaskController::index');
        $routes->post('tasks', 'Api\TaskController::create');
    });


    $routes->group('protected', ['filter' => 'jwtAuth'], function($routes) {
        $routes->get('test', 'Api\ProtectedController::index');
    });
});