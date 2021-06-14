<?php
namespace App;

class Middleware {
    private $app;
    private $container;
    
    function __construct($app) {
        $this->app = $app;
        $container = $app->getContainer(); // Dependency injection container
        $this->container = $container;
        $this->cors();
        $this->jwt();
    }
    
    // CORS
    function cors() {
        $this->app->add(function ($req, $res, $next) {
            $response = $next($req, $res);
            return $response->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        });
    }
    
    // JWT Authentication (tuupola/slim-jwt-auth)
    function jwt() {
        $this->container->get('db'); // JWT middleware callbacks dependent on DB, make sure Eloquent is initalized
        $this->app->add(new \Tuupola\Middleware\JwtAuthentication([
            "attribute" => "jwt",
            "path" => ["/"],
            "ignore" => ["/users","/ev","/testev"],
            "secret" => \App\Config\Config::auth()['secret'],
            "logger" => $this->container['logger'],
            "error" => function ($response, $arguments) {
                return $response->withJson([
                    'error' => true,
                    'errors' => $arguments["message"]
                ], 401)
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            },
            "before" => function ($request, $arguments) {
                $user = \App\Models\User::find($arguments['decoded']['sub']);
                //var_dump($user);
                return $request->withAttribute("user", $user);
            }
        ]));
    }
}