<?php

require 'MyApp.class.php';
$app = new MyApp();

require realpath(__DIR__.'/../..').'/autoload.php';

if (PHP_SAPI === 'cli') {
    $app = new \MFS\AppServer\Middleware\PHP_Compat\PHP_Compat($app);
    $handler = new \MFS\AppServer\SCGI\Handler('tcp://127.0.0.1:9999');
} else {
    ini_set('display_errors', 'Off');
    $handler = new \MFS\AppServer\MOD_PHP\Handler();
}


$handler->serve($app);
