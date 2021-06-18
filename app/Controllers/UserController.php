<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Includes\ValidationRules as ValidationRules;
use \App\Models\User as User;

class UserController {
    private $logger;
    private $db;
    private $validator;
    
    private $table;

    // Dependency injection via constructor
    public function __construct($depLogger, $depDB, $depValidator) {
        $this->logger = $depLogger;
        $this->db = $depDB;
        $this->validator = $depValidator;
        $this->table = $this->db->table('users');
    }
    
    // POST /users
    // Create user
    public function create(Request $request, Response $response) {
        $this->logger->addInfo('Creacion de usuario');
        $data = $request->getParsedBody();
        $errors = [];
        // The validate method returns the validator instance
        $validator = $this->validator->validate($request, ValidationRules::usersPost());
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        }
        if (!$errors && User::where(['username' => $data['username']])->first()) {
            $errors[] = 'Username already exists';
        }
        if (!$errors) {
            // Input is valid, so let's do something...
            $newUser = User::create([
                'username' => $data['username'],
                'password' => $data['password']
            ]);
            return $response->withJson([
                'error' => true,
                'id' => $newUser->id
            ], 200);
        } else {
            // Error occured
            return $response->withJson([
                'error' => false,
                'log' => $errors
            ], 400);
        }
    }
    
    // POST /users/login
    public function login(Request $request, Response $response) {
        
        $data = $request->getParsedBody();
        $errors = [];
        $validator = $this->validator->validate($request, ValidationRules::authPost());
        // Validate input
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        }
        // validate username
        if (!$errors && !($user = User::where(['username' => $data['username']])->first())) {
            $errors[] = 'Usuario invalido';
        }
        // validate password
        if (!$errors && !password_verify($data['password'], $user->password)) {
            $this->logger->addWarning('Inicio de sesion fallido, user: '.$user->username);
            $errors[] = 'Contraseña invalida';
        }
        if (!$errors) {

            $this->logger->addInfo('Inicio de sesion exitoso, user: '.$user->username);
            // No errors, generate JWT
            $token = $user->tokenCreate();
            // return token
            return $response->withJson([
                "error" => false,
                "message" => "Iniciaste sesion con exito",
                "token" => $token['token'],
                "expires" => $token['expires'],
                "user" => $user
            ], 200);
        } else {
            // Error occured
            return $response->withJson([
                'error' => true,
                'message' => "error de sesion",
                'log' => $errors
            ], 400);
        }
    }

    public function checkAuth(Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $this->logger->addInfo('Usuario '.$user->username.' reanudo su sesion');

        //var_dump($user);
        
        return $response->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')->withJson([
            'error' => false,
            'message' => "Bienvenido de vuelta",
            'isAuth' => true, //necesario para el front end
            'user' => $user
        ], 200);;

    }

    public function getLog(Request $request, Response $response) {
        //$this->logger->addInfo('GET /getLog');
        //$user = $request->getAttribute('user');
 
        $file = __DIR__ . "/../../logs/app.log";

        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . basename($file) . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));

        readfile($file);

        return $response;
        /**/

    }
    
}