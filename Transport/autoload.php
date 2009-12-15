<?php

namespace MFS\AppServer\Transport;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $files = array(
            __NAMESPACE__.'\LibEvent'					  => __DIR__.'/LibEvent.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Transport\autoload');
