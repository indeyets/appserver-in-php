<?php

function SCGI_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $root_of_http = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'HTTP').DIRECTORY_SEPARATOR;

        $files = array(
            'MFS::AppServer::SCGI::Application' => $root.'SCGI_Application.Class.php',
            'MFS::AppServer::SCGI::Response' => $root.'SCGI_Response.Class.php',
            'MFS::AppServer::SCGI::Exception' => $root.'exceptions.php',

            'MFS::AppServer::HTTP::Request' => $root_of_http.'Request.class.php',
            'MFS::AppServer::HTTP::GetRequest' => $root_of_http.'GetRequest.class.php',
            'MFS::AppServer::HTTP::HeadRequest' => $root_of_http.'HeadRequest.class.php',
            'MFS::AppServer::HTTP::PostRequest' => $root_of_http.'PostRequest.class.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('SCGI_autoload');
