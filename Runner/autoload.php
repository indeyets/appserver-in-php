<?php

namespace MFS\AppServer\Runner;

function autoload($class_name)
{
    static $files = null;

    if (null === $files) {
        $root = __DIR__.'/';

        $files = array(
            // low-level stuff
            'MFS\AppServer\Runner\RunnerApp'    => $root.'RunnerApp.class.php',
            'MFS\AppServer\Runner\Runner'       => $root.'Runner.class.php',
            'MFS\AppServer\Runner\Exception'    => $root.'exceptions.php',
        );
    }

    if (isset($files[$class_name]))
        require $files[$class_name];
}

spl_autoload_register('MFS\AppServer\Runner\autoload');

require 'pake/init.php';
require realpath(__DIR__.'/../autoload.php');
