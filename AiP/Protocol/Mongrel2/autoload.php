<?php

namespace MFS\AppServer\Mongrel2;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            // high-level stuff
            'MFS\AppServer\Mongrel2\Server' => $root.'Server.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Mongrel2\autoload');
