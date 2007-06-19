<?php

function SCGI_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).DIRECTORY_SEPARATOR;

        $files = array(
            'SCGI_Application' => $root.'SCGI_Application.Class.php',
            'SCGI_Request' => $root.'SCGI_Request.Class.php',
            'SCGI_Response' => $root.'SCGI_Response.Class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('SCGI_autoload');