<?php

require 'MyApp.class.php';
require realpath(__DIR__.'/../..').'/autoload.php';

use \MFS\AppServer\Middleware\PHP_Compat\PHP_Compat as php_compat;
use \MFS\AppServer\Middleware\URLMap\URLMap as urlmap;
use \MFS\AppServer\SCGI\Handler as scgi;
use \MFS\AppServer\HTTP\Handler as http;
use \MFS\AppServer\MOD_PHP\Handler as mod_php;


// init application
$app = new MyApp();

if (PHP_SAPI === 'cli') {
    // app expects php-style vars. in SCGI-mode we need them using middleware
    $app = new php_compat($app);
}

// serving hello-app on "/hello", other way serving $app
$app = new urlmap(array(
    '/hello' => function(){ return array(200, array('Content-type', 'text/plain'), 'Hello, world!'); },
    '/' => $app
));

// choosing appropriate handler
if (PHP_SAPI === 'cli') {
    if (isset($argv[1]) and $argv[1] === 'http') {
        $handler = new http('tcp://127.0.0.1:8080');
    } else {
        $handler = new scgi('tcp://127.0.0.1:9999');
    }
} else {
    ini_set('display_errors', 'Off');
    $handler = new mod_php();
}

// serving app
$handler->serve($app);
