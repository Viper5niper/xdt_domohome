<?php
namespace App\Routes;

class Eventos {
    function __construct($app) {
        $app->post('/ev/{key}', '\App\Controllers\EventosController:main');
        $app->post('/testev', '\App\Controllers\EventosController:generateTestData');
        //$app->post('/todo', '\App\Controllers\TodoController:create');
        //$app->put('/todo/{id}', '\App\Controllers\TodoController:update');
        //$app->delete('/todo/{id}', '\App\Controllers\TodoController:delete');
    }
}