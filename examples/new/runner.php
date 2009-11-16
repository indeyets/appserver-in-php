<?php

require 'MyApp.class.php';

if (PHP_SAPI === 'cli') {
    require __DIR__.'/../../SCGI/autoload.php';
    $handler = new MFS\AppServer\SCGI\Handler('tcp://127.0.0.1:9999');
} else {
    ini_set('display_errors', 'Off');
    require __DIR__.'/../../MOD_PHP/autoload.php';
    $handler = new MFS\AppServer\MOD_PHP\Handler();
}

$handler->serve(new MyApp());
