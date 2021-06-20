<?php
namespace App\Routes;

class Aspersores {
    function __construct($app) {
        $app->post('/asp/{id}/{orden}', '\App\Controllers\AspersoresController:controlAspersor');
        //$app->post('/puertastodas/{orden}', '\App\Controllers\AspersoresController:controlTodos');
        $app->get('/asp', '\App\Controllers\AspersoresController:getStates');
        $app->post('/pasp/{id}/{orden}', '\App\Controllers\AspersoresController:programarAsp');
        //$app->post('/todo', '\App\Controllers\TodoController:create');
        //$app->put('/todo/{id}', '\App\Controllers\TodoController:update');
        //$app->delete('/todo/{id}', '\App\Controllers\TodoController:delete');
    }
}