<?php
namespace App\Config;

class Config {
    // Database settings
    public function db() {
        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'domohome',
            'username' => 'root',
            'password' => 'ues-tmngr',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ];
    }
    // Slim settings
    public function slim() {
        return [
            'settings' => [
                'determineRouteBeforeAppMiddleware' => false,
                'displayErrorDetails' => true,
                'db' => self::db()
            ],
        ];
    }
    // Auth settings
    public function auth() {
        return [
            'secret' => 'D0M0H0M3',
            'expires' => 20000, // in minutes
            'hash' => PASSWORD_DEFAULT,
            'jwt' => 'HS256'
        ];
    }
}