<?php

function MFS_AppServer_Middleware_Logger_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            'MFS_AppServer_Middleware_Logger' => $root.'Logger.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_Middleware_Logger_autoload');
