<?php

namespace App\Controllers;

use App\Models\Aspersor as Aspersor;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;

class AspersoresController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('aspersores');
    }
    
    // POST /asp/{id}/{orden}
    public function controlAspersor(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /puerta/'.$args['id'].'/'.$args['orden']);
        $user = $request->getAttribute('user');
        $luz = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrAspersores = ["AP"];
        
        $aspersor = Aspersor::where('asp_key',$args['id'])->first();

        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!$aspersor) $errors = ['Aspersor escogido no existe'];

        if($orden !== "R" && $orden !== "L" && $orden !== "A") $errors = ['Orden invalida'];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la luz escogida
            $writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            //$writtenBytes = fputs($fp, 'CPE');
            
            if($orden == "R"){
                $aspersor->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Aspersores en sentido derecho";
            }else if($orden == "L"){
                $aspersor->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Aspersores en sentido izquierdo";
            }else if($orden == "A"){
                $aspersor->encendida = false;
                $toggle = $args['id'] . "/R";
                $msg = "Aspersores apagados";
            }

            $aspersor->direccion = $orden;
            $aspersor->save();

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
                'message' => "Ocurrieron errores",
                'log' => $errors
            ], 400);
        }
    }

    /*
    public function controlTodos(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /puertastodas/'.$args['orden']);
        $user = $request->getAttribute('user');
        $orden = $args['orden'];
        $errors = [];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que enciendan todas las luces
            $writtenBytes = fputs($fp, "CT". $orden);    //Agregamos la orden

            if($orden == "E"){
                $toggle = "A";
                $msg = "Pueras abiertas";
            }else{
                $toggle = "E";
                $msg = "Pueras cerradas";
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
    }*/
    
    
}