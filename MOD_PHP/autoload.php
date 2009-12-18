<?php

function MFS_AppServer_MOD_PHP_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            'MFS_AppServer_MOD_PHP_Handler'                => $root.'Handler.class.php',
            'MFS_AppServer_MOD_PHP_Response'               => $root.'Response.class.php',
            'MFS_AppServer_MOD_PHP_Cookies'                => $root.'Cookies.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_MOD_PHP_autoload');
