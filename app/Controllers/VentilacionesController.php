<?php

namespace App\Controllers;

use App\Models\Vent as Vent;
use App\Models\Evento as Evento;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;

use Illuminate\Database\Capsule\Manager as DB;
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
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.', obtuvo el estado de todas las ventilaciones');
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
        
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.' con ventilacion '.$args['id'].'. Accion: '.$args['orden']);

        $ventilacion = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrVent = ["VA","VB"];

        $vent = Vent::where('dkey',$args['id'])->first();

        //Vemos si la ventilacion solicitada se encuentra entre las opciones disponibles
        if(!$vent) $errors = ['Ventilacion escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la ventilacion escogida
            //$writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
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
                'siguiente' => $toggle,
                'newState' => $vent
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
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.' con todas las ventilaciones. Accion: '.$args['orden']);
        $orden = $args['orden'];
        $errors = [];

        // exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        // $fp = @fopen ("COM2", "w+");

        // if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            $aux = $orden === 'E';//Convertimos a booleano ( E = true, A = false)
            //Indicamos al arduino que enciendan todas las vents
            //$writtenBytes = fputs($fp, "VT". $orden);    //Agregamos la orden
            DB::table('ventiladores')->where('encendida', '=', !$aux)->update(array('encendida' => $aux));

            $vents = Vent::get();

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
                'siguiente' => $toggle,
                'newState' => $vents
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


    public function programarVent(Request $request, Response $response, $args) {
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Usuario '.$user->username.' programo la ventilacion '.$args['id'].'. Accion: '.$args['orden']);
        $luz = $args['id'];
        $orden = $args['orden'];
        $data = $request->getParsedBody();
        $errors = [];

        
        $luz = Vent::where('dkey',$args['id'])->first();
        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!$luz) $errors = ['ventilacion escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        if(!isset($data['hora'])) $errors = ['por favor especifique una hora'];

        if(!$errors && !$this->is_timestamp($data['hora'])) $errors = ['ingrese un timestamp valido'];
        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            $newEvento = Evento::create([
                'tabla' => 'ventiladores',
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
    
    
}