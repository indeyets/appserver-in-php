<?php

namespace MFS\AppServer;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\iHandler' => $root.'interfaces.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\autoload');
