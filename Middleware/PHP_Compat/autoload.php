<?php

namespace MFS\AppServer\Middleware\PHP_Compat;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\PHP_Compat\PHP_Compat'               => $root.'PHP_Compat.class.php',
            'MFS\AppServer\Middleware\PHP_Compat\Cookies'                  => $root.'Cookies.class.php',
            'MFS\AppServer\Middleware\PHP_Compat\StringStreamKeeper'       => $root.'StringStream.class.php',
            'MFS\AppServer\Middleware\PHP_Compat\StringStream'             => $root.'StringStream.class.php',

            'MFS\AppServer\Middleware\PHP_Compat\InvalidArgumentException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\PHP_Compat\UnexpectedValueException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\PHP_Compat\BadProtocolException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\PHP_Compat\autoload');
