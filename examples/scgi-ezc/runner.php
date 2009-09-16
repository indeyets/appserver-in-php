<?php
define('PROJ_ROOT', realpath(__DIR__.'/../..'));
define('EZ_ROOT', realpath(PROJ_ROOT.'/../ezcomponents'));

ini_set('include_path', '.:'.PROJ_ROOT.':'.EZ_ROOT);

// ezComponents
require 'Base/src/base.php';
spl_autoload_register('ezcBase::autoload');

// application logic
require 'GraphApp.class.php';

if (PHP_SAPI == 'cli') {
    require 'SCGI_GraphApp.class.php'; // SCGI wrapper around GraphApp

    // starting SCGI-server
    $obj = new SCGI_GraphApp();
    $obj->runloop();
} else {
    // this is a usual web-server request
    GraphApp::main();
}