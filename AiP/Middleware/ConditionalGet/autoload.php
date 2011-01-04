<?php

namespace MFS\AppServer\Middleware\ConditionalGet;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\ConditionalGet\ConditionalGet'           => $root.'ConditionalGet.class.php',
            // 'MFS\AppServer\Middleware\ConditionalGet\UnexpectedValueException' => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\ConditionalGet\autoload');
