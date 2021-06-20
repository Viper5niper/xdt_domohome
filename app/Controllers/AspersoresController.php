<?php

namespace App\Controllers;

use App\Models\Aspersor as Aspersor;
use App\Models\Evento as Evento;

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
    
    //GET /asp
    public function getStates(Request $request, Response $response, $args) {

        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.', obtuvo el estado de todos los aspersores');
        $errors = [];

        $asps = Aspersor::get();

        if(!$errors)
        {   
            return $response->withJson([
                'error' => false,
                'message' => 'Puertas obtenidas',
                'data' => $asps ? $asps : []
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

    // POST /asp/{id}/{orden}
    public function controlAspersor(Request $request, Response $response, $args) {
        
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.' con aspersor '.$args['id'].'. Accion: '.$args['orden']);

        $luz = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrAspersores = ["AP"];
        
        $aspersor = Aspersor::where('dkey',$args['id'])->first();

        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!$aspersor) $errors = ['Aspersor escogido no existe'];

        if($orden !== "E" && $orden !== "L" && $orden !== "A") $errors = ['Orden invalida'];

        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la luz escogida
            //$writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            //$writtenBytes = fputs($fp, 'CPE');
            
            if($orden == "E"){
                $aspersor->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Aspersores en sentido derecho";
            }else if($orden == "L"){
                $aspersor->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Aspersores en sentido izquierdo";
            }else if($orden == "A"){
                $aspersor->encendida = false;
                $toggle = $args['id'] . "/E";
                $msg = "Aspersores apagados";
            }

            $aspersor->direccion = $orden;
            $aspersor->save();

            return $response->withJson([
                'error' => false,
                'message' => $msg,
                'payload' => $args['id'] . $orden,
                'siguiente' => $toggle,
                'newState' => $aspersor
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

    // POST /pluz/{id}/{orden}
    public function programarAsp(Request $request, Response $response, $args) {
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Usuario '.$user->username.' programo el aspersor '.$args['id'].'. Accion: '.$args['orden']);
        $luz = $args['id'];
        $orden = $args['orden'];
        $data = $request->getParsedBody();
        $errors = [];
        
        $luz = Aspersor::where('dkey',$args['id'])->first();
        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!$luz) $errors = ['aspersor escogido no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        if(!isset($data['hora'])) $errors = ['por favor especifique una hora'];

        if(!$errors && !$this->is_timestamp($data['hora'])) $errors = ['ingrese un timestamp valido'];
        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            $newEvento = Evento::create([
                'tabla' => 'aspersores',
                'dkey' => $args['id'],
                'orden' => $orden,
                'payload' => $args['id'] . $orden,
                'hora' => $data['hora']
            ]);
            
            if($orden == "E"){
                $toggle = $args['id'] . "/A";
                $msg = "Se ha programado el encendido";
            }else{
                $toggle = $args['id'] . "/E";
                $msg = "Se ha programado el apagado";
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
                'message' => "error al programar",
                'log' => $errors
            ], 400);
        }
    }
    
    function is_timestamp($timestamp) {
        if(strtotime(date('d-m-Y H:i:s',$timestamp)) === (int)$timestamp) {
            return $timestamp;
        } else return false;
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