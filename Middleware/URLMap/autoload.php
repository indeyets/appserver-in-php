<?php

namespace MFS\AppServer\Middleware\URLMap;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\URLMap\URLMap'                   => $root.'URLMap.class.php',
            'MFS\AppServer\Middleware\URLMap\InvalidArgumentException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\URLMap\UnexpectedValueException' => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\URLMap\autoload');
