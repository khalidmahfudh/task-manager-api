<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', '');

$routes->group('api', function($routes) {

    // Auth API (URL: /api/auth)
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
            $routes->put('password', 'Api\ProfileController::updatePassword');
        });

        // Tasks API (URL: /api/tasks)
        // $routes->resource('tasks', ['controller' => 'Api\TaskController', 'except' => ['new', 'edit']]);
        $routes->group('tasks', function($routes) {
            $routes->get('/', 'Api\TaskController::index');
            $routes->post('/', 'Api\TaskController::create');
            $routes->get('(:num)', 'Api\TaskController::show/$1');
            $routes->put('(:num)', 'Api\TaskController::update/$1');
            $routes->patch('(:num)', 'Api\TaskController::update/$1'); 
            $routes->delete('(:num)', 'Api\TaskController::delete/$1');
        });

        // Admin API (URL: /api/admin)
        $routes->group('admin', ['filter' => 'adminCheck'], function($routes) {
            $routes->get('users', 'Api\AdminController::index');
            $routes->post('users', 'Api\AdminController::create');
            $routes->get('users/(:num)', 'Api\AdminController::show/$1');
            $routes->put('users/(:num)', 'Api\AdminController::update/$1');  
            $routes->patch('users/(:num)', 'Api\AdminController::update/$1');
            $routes->delete('users/(:num)', 'Api\AdminController::delete/$1');

            $routes->get('tasks', 'Api\AdminController::getAllTasks');
        });
    });


    $routes->group('protected', ['filter' => ['jwtAuth', 'adminCheck']], function($routes) {
        $routes->get('test', 'Api\ProtectedController::index');
    });
});