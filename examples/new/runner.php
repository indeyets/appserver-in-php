<?php

$root = realpath(__DIR__.'/../..');

require 'MyApp.class.php';
$app = new MyApp();

if (PHP_SAPI === 'cli') {
    require $root.'/SCGI/autoload.php';
    $handler = new MFS\AppServer\SCGI\Handler('tcp://127.0.0.1:9999');

    require $root.'/Middleware/PHP_Compat/autoload.php';
    $app = new \MFS\AppServer\Middleware\PHP_Compat\PHP_Compat($app);
} else {
    ini_set('display_errors', 'Off');
    require $root.'/MOD_PHP/autoload.php';
    $handler = new MFS\AppServer\MOD_PHP\Handler();
}


$handler->serve($app);
