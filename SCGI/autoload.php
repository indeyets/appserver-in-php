<?php

namespace MFS\AppServer\SCGI;

require realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'HTTP').DIRECTORY_SEPARATOR.'autoload.php';

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).DIRECTORY_SEPARATOR;

        $files = array(
            'MFS\AppServer\SCGI\Application' => $root.'SCGI_Application.Class.php',
            'MFS\AppServer\SCGI\Response' => $root.'SCGI_Response.Class.php',
            'MFS\AppServer\SCGI\Exception' => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\SCGI\autoload');
