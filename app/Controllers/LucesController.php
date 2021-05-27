<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;
//TODO: Guardar las fechas de encendido y apagado de las luces para hacer calculos de consumo de energia
class LucesController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('todo');
    }
    
    // POST /luces/{id}/{orden}
    public function controlLuz(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /luces/'.$args['id'].'/'.$args['orden']);
        $user = $request->getAttribute('user');
        $luz = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrLuces = ["LG","LP","LA","LB","LS","LC","LE"];

        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!in_array($args['id'], $arrLuces)) $errors = ['Luz escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la luz escogida
            $writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            //$writtenBytes = fputs($fp, 'LGA');
            
            if($orden == "E"){
                $toggle = $args['id'] . "/A";
                $msg = "Luz encendida";
            }else{
                $toggle = $args['id'] . "/E";
                $msg = "Luz apagada";
            }

            return $response->withJson([
                'error' => false,
                'message' => $msg,
                'payload' => $args['id'] . $orden,
                'siguiente' => $toggle
            ], 200);
        }
        else{
            return $response->withJson([
                'error' => true,
                'message' => "error al encender",
                'log' => $errors
            ], 400);
        }
    }


    public function controlTodas(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /lucestodas/'.$args['orden']);
        $user = $request->getAttribute('user');
        $orden = $args['orden'];
        $errors = [];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que enciendan todas las luces
            $writtenBytes = fputs($fp, "LT". $orden);    //Agregamos la orden

            if($orden == "E"){
                $toggle = "A";
                $msg = "Luces encendidas";
            }else{
                $toggle = "E";
                $msg = "Luces apagadas";
            }

            return $response->withJson([
                'error' => false,
                'message' => $msg,
                'payload' => "LT" . $orden,
                'siguiente' => $toggle
            ], 200);
        }
        else{
            return $response->withJson([
                'error' => true,
                'message' => "error al encender",
                'log' => $errors
            ], 400);
        }
    }
    
    
}