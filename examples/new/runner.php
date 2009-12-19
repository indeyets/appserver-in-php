<?php

require 'MyApp.class.php';
require dirname(__FILE__).'/../../autoload.php';

// init application
$app = new MyApp();

if (PHP_SAPI === 'cli') {
    // app expects php-style vars. in SCGI-mode we need them using middleware
    $app = new MFS_AppServer_Middleware_PHP_Compat(array($app, '__invoke'));
}

// serving hello-app on "/hello", other way serving $app
$app = new MFS_AppServer_Middleware_URLMap(array(
    '/hello' => 'sample_hello_handler',
    '/' => array($app, '__invoke'),
));

function sample_hello_handler()
{
    return array(200, array('Content-type', 'text/plain'), 'Hello, world!');
}

// choosing appropriate handler
if (PHP_SAPI === 'cli') {
    if (isset($argv[1]) and $argv[1] === 'http') {
        $handler = new MFS_AppServer_DaemonicHandler('tcp://127.0.0.1:8080', 'HTTP');
    } else {
        $handler = new MFS_AppServer_DaemonicHandler('tcp://127.0.0.1:9999', 'SCGI');
    }
} else {
    ini_set('display_errors', 'Off');
    $handler = new MFS_AppServer_MOD_PHP_Handler();
}

// serving app
$handler->serve(array($app, '__invoke'));
