<?php

namespace MFS\AppServer\HTTP;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            'MFS\AppServer\HTTP\Request'                    => $root.'Request.class.php',
            'MFS\AppServer\HTTP\GetRequest'                 => $root.'GetRequest.class.php',
            'MFS\AppServer\HTTP\HeadRequest'                => $root.'HeadRequest.class.php',
            'MFS\AppServer\HTTP\PostRequest'                => $root.'PostRequest.class.php',
            'MFS\AppServer\HTTP\UnknownRequest'             => $root.'UnknownRequest.class.php',

            'MFS\AppServer\HTTP\UnexpectedValueException'   => $root.'exceptions.php',
            'MFS\AppServer\HTTP\BadProtocolException'       => $root.'exceptions.php',

            'MFS\AppServer\HTTP\iRequest'                   => $root.'interfaces.php',
            'MFS\AppServer\HTTP\iGetRequest'                => $root.'interfaces.php',
            'MFS\AppServer\HTTP\iHeadRequest'               => $root.'interfaces.php',
            'MFS\AppServer\HTTP\iPostRequest'               => $root.'interfaces.php',
            'MFS\AppServer\HTTP\iUnknownRequest'            => $root.'interfaces.php',
            'MFS\AppServer\HTTP\iResponse'                  => $root.'interfaces.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\HTTP\autoload');
