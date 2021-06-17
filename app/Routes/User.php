<?php
namespace App\Routes;

class User {
    function __construct($app) {
        $app->post('/users', '\App\Controllers\UserController:create');
        $app->post('/users/login', '\App\Controllers\UserController:login');
        $app->get('/checkAuth', '\App\Controllers\UserController:checkAuth');
        $app->get('/getLog', '\App\Controllers\UserController:getLog');
    }
}