<?php
namespace App\Routes;

class Luces {
    function __construct($app) {
        $app->post('/luces/{id}/{orden}', '\App\Controllers\LucesController:controlLuz');
        $app->post('/lucestodas/{orden}', '\App\Controllers\LucesController:controlTodas');
        $app->post('/pluz/{id}/{orden}', '\App\Controllers\LucesController:programarLuz');
        $app->get('/luces', '\App\Controllers\LucesController:getStates');
        //$app->post('/todo', '\App\Controllers\TodoController:create');
        //$app->put('/todo/{id}', '\App\Controllers\TodoController:update');
        //$app->delete('/todo/{id}', '\App\Controllers\TodoController:delete');
    }
}