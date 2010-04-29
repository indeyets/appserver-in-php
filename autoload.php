<?php

function MFS_AppServer_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            'MFS_AppServer_iHandler'                              => $root.'interfaces.php',
            'MFS_AppServer_iProtocol'                             => $root.'interfaces.php',
            'MFS_AppServer_DaemonicHandler'                       => $root.'DaemonicHandler.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_autoload');


// enabling components
require dirname(__FILE__).'/Transport/autoload.php';

require dirname(__FILE__).'/SCGI/autoload.php';
require dirname(__FILE__).'/HTTP/autoload.php';
require dirname(__FILE__).'/MOD_PHP/autoload.php';

require dirname(__FILE__).'/Middleware/PHP_Compat/autoload.php';
require dirname(__FILE__).'/Middleware/Session/autoload.php';
require dirname(__FILE__).'/Middleware/URLMap/autoload.php';
require dirname(__FILE__).'/Middleware/Logger/autoload.php';

require dirname(__FILE__).'/Apps/FileServe/autoload.php';
