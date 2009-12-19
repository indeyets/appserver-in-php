<?php

require 'MyApp.class.php';
require realpath(__DIR__.'/../..').'/autoload.php';

// init application
$app = new MyApp();

if (PHP_SAPI === 'cli') {
    // app expects php-style vars. in SCGI-mode we need them using middleware
    $app = new \MFS\AppServer\Middleware\PHP_Compat\PHP_Compat($app);
}

// serving hello-app on "/hello", other way serving $app
$app = new \MFS\AppServer\Middleware\URLMap\URLMap(array(
    '/hello' => function(){ return array(200, array('Content-type', 'text/plain'), 'Hello, world!'); },
    '/' => $app
));

// choosing appropriate handler
if (PHP_SAPI === 'cli') {
    if (isset($argv[1]) and $argv[1] === 'http') {
        $handler = new \MFS\AppServer\DaemonicHandler('tcp://127.0.0.1:8080', 'HTTP');
    } else {
        $handler = new \MFS\AppServer\DaemonicHandler('tcp://127.0.0.1:9999', 'SCGI');
    }
} else {
    ini_set('display_errors', 'Off');
    $handler = new \MFS\AppServer\MOD_PHP\Handler();
}

// serving app
$handler->serve($app);
