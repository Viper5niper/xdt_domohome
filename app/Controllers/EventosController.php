<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use App\Models\Evento as Evento;
use Illuminate\Database\Capsule\Manager as DB;

class EventosController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('eventos');
    }
    
    public function main(Request $request, Response $response, $args) {
        
        $key = $args['key'];
        $errors = [];

        if(!isset($key) || $key !== \App\Config\Config::auth()['secret'])
        $errors = ['No tiene permitido el acceso a esta funcion'];

        exec("mode COM2 BAUD=9600 PARITY=N data=8 stop=1 xon=off");
        $fp = @fopen ("COM2", "w+");

        if (!$fp) $errors = ["Puerto serial no accesible"];

        if(!$errors)
        {   
        
            $desde = date("Y-m-d H:i:s");
            //Ejecutamos todos los eventos de los proximos 5 minutos
            $hasta = date('Y-m-d H:i:s',strtotime('+5 minutes',strtotime($desde)));

            $eventos = Evento::whereBetween('hora', array($desde, $hasta))->get();

            foreach ($eventos as $evento) {
                //var_dump($evento->hora);

                //no identificamos aspersores porque vale nionga pa que lado vayan
                //Solo cambiar R por E

                DB::table($evento->tabla)->where('dkey',$evento->dkey)  //Buscamos el dispositivo que se quiere editar
                //editamos el estado (encendido o apagado)
                ->update(['encendida' => $evento->orden === 'E']);//devolvera true o false dependiendo de si se cumple o no la condicion
                //esto funciona porque ya se valido que la orden sea E o A a la hora de programar el evento
                echo $evento->payload;
                $writtenBytes = fputs($fp, $evento->payload);

                if($evento->tabla === 'cerrojos') sleep(5); //Esperamos a que se abra la puerta
                else sleep(1) //Esperamos a que se cumpla el delay del arduino

            }

            return $response/*->withJson([
                'error' => false,
                'message' => 'Eventos ejecutados'
            ], 200)/**/;

        }
        else{
            return $response->withJson([
                'error' => true,
                'message' => "Eventos no ejecutados",
                'log' => $errors
            ], 400);
        }
   
    }
    
    public function generateTestData(Request $request, Response $response, $args) {
        

        $now = date("Y-m-d H:i:s");
        $one = date('Y-m-d H:i:s',strtotime('-2 minutes',strtotime($now)));
        $two = date('Y-m-d H:i:s',strtotime('+3 minutes',strtotime($now)));
        $three = date('Y-m-d H:i:s',strtotime('+4 minutes',strtotime($now)));
        $four = date('Y-m-d H:i:s',strtotime('+15 minutes',strtotime($now)));

        Evento::create([
            'tabla' => 'luces',
            'dkey' => 'LG',
            'orden' => 'E',
            'payload' => 'LGE',
            'hora' => $one
        ]);

        Evento::create([
            'tabla' => 'luces',
            'dkey' => 'LP',
            'orden' => 'E',
            'payload' => 'LPE',
            'hora' => $two
        ]);

        Evento::create([
            'tabla' => 'luces',
            'dkey' => 'LA',
            'orden' => 'E',
            'payload' => 'LAE',
            'hora' => $three
        ]);

        Evento::create([
            'tabla' => 'luces',
            'dkey' => 'LB',
            'orden' => 'E',
            'payload' => 'LBE',
            'hora' => $four
        ]);
            
        return $response;
   
    }
}
