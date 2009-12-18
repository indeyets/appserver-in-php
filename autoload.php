<?php

namespace MFS\AppServer;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\iHandler'        => $root.'interfaces.php',
            'MFS\AppServer\iProtocol'       => $root.'interfaces.php',
            'MFS\AppServer\DaemonicHandler' => $root.'DaemonicHandler.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\autoload');


// enabling components
require __DIR__.'/Transport/autoload.php';

require __DIR__.'/SCGI/autoload.php';
require __DIR__.'/HTTP/autoload.php';
require __DIR__.'/MOD_PHP/autoload.php';

require __DIR__.'/Middleware/PHP_Compat/autoload.php';
require __DIR__.'/Middleware/Session/autoload.php';
require __DIR__.'/Middleware/URLMap/autoload.php';
