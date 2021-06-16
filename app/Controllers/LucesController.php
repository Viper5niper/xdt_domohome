<?php

namespace App\Controllers;

use App\Models\Luz as Luz;
use App\Models\Evento as Evento;

use Illuminate\Database\Capsule\Manager as DB;

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
        $this->table = $this->db->table('luces');
    }
    
    public function getStates(Request $request, Response $response, $args) {
        $this->logger->addInfo('GET /luces');
        $user = $request->getAttribute('user');
        $errors = [];

        $luces = Luz::get();

        if(!$errors)
        {   

            return $response->withJson([
                'error' => false,
                'message' => 'luces obtenidas',
                'data' => $luces ? $luces : []
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

    // POST /luces/{id}/{orden}
    public function controlLuz(Request $request, Response $response, $args) {
        
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Interaccion de usuario '.$user->username.' con Luz '.$args['id'].'. Accion: '.$args['orden']);

        $luz = $args['id'];
        $orden = $args['orden'];
        $errors = [];

        $arrLuces = ["LG","LP","LA","LB","LS","LC","LE"];

        $luz = Luz::where('dkey',$args['id'])->first();

        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        //if(!in_array($args['id'], $arrLuces)) $errors = ['Luz escogida no existe'];

        if(!$luz) $errors = ['Luz escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            //Indicamos al arduino que encienda la luz escogida
            //$writtenBytes = fputs($fp, $args['id'] . $orden);    //Agregamos la orden
            
            if($orden == "E"){
                $luz->encendida = true;
                $toggle = $args['id'] . "/A";
                $msg = "Luz encendida";
            }else{
                $luz->encendida = false;
                $toggle = $args['id'] . "/E";
                $msg = "Luz apagada";
            }

            $luz->save();

            return $response->withJson([
                'error' => false,
                'message' => $msg,
                'payload' => $args['id'] . $orden,
                'siguiente' => $toggle,
                'newState' => $luz
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

    // POST /lucestodas/{orden}
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
            $aux = $orden === 'E';//Convertimos a booleano ( E = true, A = false)

            //Invertimos el estado de las luces
            DB::table('luces')->where('encendida', '=', !$aux)->update(array('encendida' => $aux));
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

    // POST /pluz/{id}/{orden}
    public function programarLuz(Request $request, Response $response, $args) {
        $this->logger->addInfo('POST /luces/'.$args['id'].'/'.$args['orden']);
        $user = $request->getAttribute('user');
        $luz = $args['id'];
        $orden = $args['orden'];
        $data = $request->getParsedBody();
        $errors = [];

        $arrLuces = ["LG","LP","LA","LB","LS","LC","LE"];
        
        $luz = Luz::where('dkey',$args['id'])->first();
        //Vemos si la luz solicitada se encuentra entre las opciones disponibles
        if(!$luz) $errors = ['Luz escogida no existe'];

        if($orden !== "E" && $orden !== "A") $errors = ['Orden invalida'];

        if(!isset($data['hora'])) $errors = ['por favor especifique una hora'];

        if(!$errors && !$this->is_timestamp($data['hora'])) $errors = ['ingrese un timestamp valido'];
        //exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        //$fp = @fopen ("COM2", "w+");

        //if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
            $newEvento = Evento::create([
                'tabla' => 'luces',
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