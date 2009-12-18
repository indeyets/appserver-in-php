<?php

function MFS_AppServer_Middleware_PHP_Compat_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            'MFS_AppServer_Middleware_PHP_Compat'                          => $root.'PHP_Compat.class.php',
            'MFS_AppServer_Middleware_PHP_Compat_Cookies'                  => $root.'Cookies.class.php',

            'MFS_AppServer_Middleware_PHP_Compat_BadProtocolException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_Middleware_PHP_Compat_autoload');
