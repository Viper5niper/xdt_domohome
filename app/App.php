<?php
namespace App;

date_default_timezone_set('America/El_Salvador');

ini_set ('display_errors', 0);

class App {
    private $app;
    
    public function __construct() {
        // initalize Slim App
        $app = new \Slim\App(\App\Config\Config::slim());
        $this->app = $app;
        // initalize dependencies
        $this->dependencies();
        // initalize middlewares
        $this->middleware();
        // initalize routes
        $this->routes();
    }
    
    public function get() {
        return $this->app;
    }
    
    private function dependencies() {
        return new \App\Dependencies($this->app);
    }
    
    private function middleware() {
        return new \App\Middleware($this->app);
    }
    
    private function routes() {
        return [
            new \App\Routes\Luces($this->app),
            new \App\Routes\Cerrojos($this->app),
            new \App\Routes\Ventilaciones($this->app),
            new \App\Routes\Aspersores($this->app),
            new \App\Routes\Eventos($this->app),
            new \App\Routes\User($this->app)
        ];
    }
}