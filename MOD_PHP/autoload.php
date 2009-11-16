<?php

namespace MFS\AppServer\MOD_PHP;

require realpath(__DIR__.'/../AppServer').'/autoload.php';
require realpath(__DIR__.'/../HTTP').'/autoload.php';

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\MOD_PHP\Handler'                => $root.'Handler.Class.php',
            'MFS\AppServer\MOD_PHP\Application'            => $root.'Application.Class.php',
            'MFS\AppServer\MOD_PHP\Response'               => $root.'Response.Class.php',
            'MFS\AppServer\MOD_PHP\Exception'              => $root.'exceptions.php',
            'MFS\AppServer\MOD_PHP\LogicException'         => $root.'exceptions.php',
            'MFS\AppServer\MOD_PHP\RuntimeException'       => $root.'exceptions.php',

            'MFS\AppServer\MOD_PHP\Request'                => $root.'Request.class.php',
            'MFS\AppServer\MOD_PHP\GetRequest'             => $root.'GetRequest.class.php',
            'MFS\AppServer\MOD_PHP\HeadRequest'            => $root.'HeadRequest.class.php',
            'MFS\AppServer\MOD_PHP\PostRequest'            => $root.'PostRequest.class.php',
            'MFS\AppServer\MOD_PHP\UnknownRequest'         => $root.'PostRequest.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\MOD_PHP\autoload');
