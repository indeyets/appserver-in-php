<?php

namespace MFS\AppServer\HTTP;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            // high-level stuff
            'MFS\AppServer\HTTP\Server' => $root.'Server.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\HTTP\autoload');
