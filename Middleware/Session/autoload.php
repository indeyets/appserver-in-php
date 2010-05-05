<?php

function MFS_AppServer_Middleware_Session_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            'MFS_AppServer_Middleware_Session'                          => $root.'Session.class.php',
            'MFS_AppServer_Middleware_Session__Engine'                  => $root.'_Engine.class.php',
            'MFS_AppServer_Middleware_Session_Storage'                  => $root.'interfaces.php',
            'MFS_AppServer_Middleware_Session_FileStorage'              => $root.'storage/FileStorage.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_Middleware_Session_autoload');
