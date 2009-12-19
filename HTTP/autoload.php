<?php

function MFS_AppServer_HTTP_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            // high-level stuff
            'MFS_AppServer_HTTP_Server' => $root.'Server.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_HTTP_autoload');
