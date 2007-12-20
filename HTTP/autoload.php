<?php

namespace MFS::AppServer::HTTP;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).DIRECTORY_SEPARATOR;

        $files = array(
            'MFS::AppServer::HTTP::Request' => $root.'Request.class.php',
            'MFS::AppServer::HTTP::GetRequest' => $root.'GetRequest.class.php',
            'MFS::AppServer::HTTP::HeadRequest' => $root.'HeadRequest.class.php',
            'MFS::AppServer::HTTP::PostRequest' => $root.'PostRequest.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS::AppServer::HTTP::autoload');
