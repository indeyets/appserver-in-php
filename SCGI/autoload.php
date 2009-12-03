<?php

namespace MFS\AppServer\SCGI;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            // low-level stuff
            'MFS\SCGI\Server'                             => $root.'Server.Class.php',
            'MFS\SCGI\Client'                             => $root.'Client.Class.php',
            'MFS\SCGI\ClientRequest'                      => $root.'ClientRequest.Class.php',

            // high-level stuff
            'MFS\AppServer\SCGI\Handler'                  => $root.'Handler.Class.php',
            'MFS\AppServer\SCGI\Response'                 => $root.'Response.Class.php',

            'MFS\AppServer\SCGI\Exception'                => $root.'exceptions.php',
            'MFS\AppServer\SCGI\LogicException'           => $root.'exceptions.php',
            'MFS\AppServer\SCGI\RuntimeException'         => $root.'exceptions.php',
            'MFS\AppServer\SCGI\UnexpectedValueException' => $root.'exceptions.php',
            'MFS\AppServer\SCGI\BadProtocolException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\SCGI\autoload');
