<?php

namespace App\Controllers;

use App\Models\Cerrojo as Cerrojo;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;

class CerrojosController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('cerrojos');
    }
    
    public function getStates(Request $request, Response $response, $args) {
        $this->logger->addInfo('GET /luces');
        $user = $request->getAttribute('user');
        $errors = [];

        $cerr = Cerrojo::get();

        if(!$errors)
        {   
            return $response->withJson([
                'error' => false,
                'message' => 'Puertas obtenidas',
                'data' => $cerr ? $cerr : []
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

    // POST /cerr/{id}/{orden}
    public function controlCerrojo(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /puerta/'.$args['id'].'/'.$args['orden']);
        $user = $request->getAttribute('user');
        $luz = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrCerrojos = ["CE","CP","CG"];

        $cerrojo = Cerrojo::where('dkey',$args['id'])->first();

        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        //if(!in_array($args['id'], $arrCerrojos)) $errors = ['Puerta escogida no existe'];

        if(!$cerrojo) $errors = ['Puerta escogida no existe'];

        if($orden !== "E" && $orden !== "A" && $orden !== "I") $errors = ['Orden invalida'];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la luz escogida
            $writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            //$writtenBytes = fputs($fp, 'CPE');
            
            if($orden == "E"){
                $cerrojo->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Puerta abierta";
            }else if($orden == "A"){
                $cerrojo->encendida = false;
                $toggle = $args['id'] . "/E";
                $msg = "Puerta cerrada";
            }else if($orden == "I"){
                $cerrojo->encendida = false;
                $toggle = $args['id'] . "/I";
                $msg = "Pase adelante";
            }  
            
            $cerrojo->save();

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