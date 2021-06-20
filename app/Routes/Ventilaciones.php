<?php
namespace App\Routes;

class Ventilaciones {
    function __construct($app) {
        $app->post('/vents/{id}/{orden}', '\App\Controllers\VentilacionesController:controlVent');
        $app->get('/vents', '\App\Controllers\VentilacionesController:getStates');
        $app->post('/ventstodas/{orden}', '\App\Controllers\VentilacionesController:controlTodas');
        $app->post('/pvent/{id}/{orden}', '\App\Controllers\VentilacionesController:programarVent');
        //$app->get('/todo/{id}', '\App\Controllers\TodoController:find');
        //$app->post('/todo', '\App\Controllers\TodoController:create');
        //$app->put('/todo/{id}', '\App\Controllers\TodoController:update');
        //$app->delete('/todo/{id}', '\App\Controllers\TodoController:delete');
    }
}