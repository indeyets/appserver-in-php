<?php

function MFS_AppServer_Transport_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $files = array(
            'MFS_AppServer_Transport_BaseTransport' => dirname(__FILE__).'/BaseTransport.class.php',
            'MFS_AppServer_Transport_LibEvent'      => dirname(__FILE__).'/LibEvent.class.php',
            'MFS_AppServer_Transport_Socket'        => dirname(__FILE__).'/Socket.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_Transport_autoload');
