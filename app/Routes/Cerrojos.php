<?php
namespace App\Routes;

class Cerrojos {
    function __construct($app) {
        $app->post('/puerta/{id}/{orden}', '\App\Controllers\CerrojosController:controlCerrojo');
        $app->get('/puertas', '\App\Controllers\CerrojosController:getStates');
        //$app->post('/puertastodas/{orden}', '\App\Controllers\CerrojosController:controlTodos');
        //$app->get('/todo/{id}', '\App\Controllers\TodoController:find');
        //$app->post('/todo', '\App\Controllers\TodoController:create');
        //$app->put('/todo/{id}', '\App\Controllers\TodoController:update');
        //$app->delete('/todo/{id}', '\App\Controllers\TodoController:delete');
    }
}