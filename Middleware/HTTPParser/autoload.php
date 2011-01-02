<?php

namespace MFS\AppServer\Middleware\HTTPParser;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\HTTPParser\HTTPParser'               => $root.'HTTPParser.class.php',
            'MFS\AppServer\Middleware\HTTPParser\Cookies'                  => $root.'Cookies.class.php',

            'MFS\AppServer\Middleware\HTTPParser\InvalidArgumentException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\HTTPParser\UnexpectedValueException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\HTTPParser\BadProtocolException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\HTTPParser\autoload');
