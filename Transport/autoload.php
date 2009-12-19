<?php

namespace MFS\AppServer\Transport;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $files = array(
            __NAMESPACE__.'\iTransport'         => __DIR__.'/interfaces.php',
            __NAMESPACE__.'\LogicException'     => __DIR__.'/exceptions.php',
            __NAMESPACE__.'\RuntimeException'   => __DIR__.'/exceptions.php',

            __NAMESPACE__.'\BaseTransport'      => __DIR__.'/BaseTransport.class.php',
            __NAMESPACE__.'\LibEvent'           => __DIR__.'/LibEvent.class.php',
            __NAMESPACE__.'\LibEventStream'     => __DIR__.'/LibEventStream.class.php',
            __NAMESPACE__.'\LibEventUnbuffered' => __DIR__.'/LibEventUnbuffered.class.php',
            __NAMESPACE__.'\Socket'             => __DIR__.'/Socket.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Transport\autoload');
