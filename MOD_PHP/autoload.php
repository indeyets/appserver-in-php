<?php

namespace MFS\AppServer\MOD_PHP;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\MOD_PHP\Handler'                => $root.'Handler.class.php',
            'MFS\AppServer\MOD_PHP\Response'               => $root.'Response.class.php',
            'MFS\AppServer\MOD_PHP\Cookies'                => $root.'Cookies.class.php',

            'MFS\AppServer\MOD_PHP\Exception'              => $root.'exceptions.php',
            'MFS\AppServer\MOD_PHP\LogicException'         => $root.'exceptions.php',
            'MFS\AppServer\MOD_PHP\RuntimeException'       => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\MOD_PHP\autoload');
