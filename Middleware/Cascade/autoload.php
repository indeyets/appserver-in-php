<?php

namespace MFS\AppServer\Middleware\Cascade;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\Cascade\Cascade'                  => $root.'Cascade.class.php',
            'MFS\AppServer\Middleware\Cascade\UnexpectedValueException' => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\Cascade\autoload');
