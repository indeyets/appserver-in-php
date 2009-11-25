<?php

namespace MFS\AppServer\Middleware\Session;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\Middleware\Session\Session'                  => $root.'PHP_Compat.class.php',
            'MFS\AppServer\Middleware\Session\_Engine'                  => $root.'_Engine.class.php',

            'MFS\AppServer\Middleware\Session\Storage'                  => $root.'interfaces.php',
            'MFS\AppServer\Middleware\Session\FileStorage'              => $root.'storage/FileStorage.class.php',

            'MFS\AppServer\Middleware\Session\RuntimeException'         => $root.'exceptions.php',
            'MFS\AppServer\Middleware\Session\LogicException'           => $root.'exceptions.php',
            'MFS\AppServer\Middleware\Session\UnexpectedValueException' => $root.'exceptions.php',
            'MFS\AppServer\Middleware\Session\OutOfBoundsException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Middleware\Session\autoload');
