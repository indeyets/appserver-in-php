<?php

function MFS_AppServer_Runner_autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = dirname(__FILE__).'/';

        $files = array(
            // low-level stuff
            'MFS_AppServer_Runner_RunnerApp'    => $root.'RunnerApp.class.php',
            'MFS_AppServer_Runner'              => $root.'Runner.class.php',
            'MFS_AppServer_Runner_Exception'    => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS_AppServer_Runner_autoload');

require 'pake/init.php';
require realpath(dirname(__FILE__).'/../autoload.php');
