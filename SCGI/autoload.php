<?php

function MFS_AppServer_SCGI_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            // low-level stuff
            'MFS_AppServer_SCGI_Server'                             => $root.'Server.class.php',
            'MFS_AppServer_SCGI_Client'                             => $root.'Client.class.php',
            'MFS_AppServer_SCGI_ClientRequest'                      => $root.'ClientRequest.class.php',

            // high-level stuff
            'MFS_AppServer_SCGI_Handler'                  => $root.'Handler.class.php',
            'MFS_AppServer_SCGI_Response'                 => $root.'Response.class.php',

            'MFS_AppServer_SCGI_BadProtocolException'     => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_SCGI_autoload');
