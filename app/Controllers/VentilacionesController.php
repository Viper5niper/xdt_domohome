<?php

namespace App\Controllers;

use App\Models\Vent as Vent;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;
//TODO: Guardar las fechas de encendido y apagado de las vents para hacer calculos de consumo de energia
class VentilacionesController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('ventiladores');
    }
    
    //GET /vents
    public function getStates(Request $request, Response $response, $args) {
        $this->logger->addInfo('GET /vents');
        $user = $request->getAttribute('user');
        $errors = [];

        $vents = Vent::get();

        if(!$errors)
        {   
            return $response->withJson([
                'error' => false,
                'message' => 'vents obtenidas',
                'data' => $vents ? $vents : []
            ], 200);
        }
        else{
            return $response->withJson([
                'error' => true,
                'message' => "error al obtener",
                'log' => $errors
            ], 400);
        }
    }

    // POST /vents/{id}/{orden}
    public function controlVent(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /vents/'.$args['id'].'/'.$args['orden']);
        $user = $request->getAttribute('user');
        $ventilacion = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrVent = ["VA","VB"];

        $vent = Vent::where('dkey',$args['id'])->first();

        //Vemos si la ventilacion solicitada se encuentra entre las opciones disponibles
        if(!$vent) $errors = ['Ventilacion escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la ventilacion escogida
            $writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            //$writtenBytes = fputs($fp, 'LGA');
            
            if($orden == "E"){
                $vent->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Ventilacion encendida";
            }else{
                $vent->encendida = false;
                $toggle = $args['id'] . "/E";
                $msg = "Ventilacion apagada";
            }

            $vent->save();

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
        $this->logger->addInfo('POST /ventstodas/'.$args['orden']);
        $user = $request->getAttribute('user');
        $orden = $args['orden'];
        $errors = [];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que enciendan todas las vents
            $writtenBytes = fputs($fp, "VT". $orden);    //Agregamos la orden

            if($orden == "E"){
                $toggle = "A";
                $msg = "vents encendidas";
            }else{
                $toggle = "E";
                $msg = "vents apagadas";
            }

            return $response->withJson([
                'error' => false,
                'message' => $msg,
                'payload' => "VT" . $orden,
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