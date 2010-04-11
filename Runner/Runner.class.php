<?php

class MFS_AppServer_Runner
{
    private $servers;

    public function __construct()
    {
        $this->servers = array();
    }

    public function addServer($app_class, array $middlewares, $protocol, $socket, $min_instances = 1, $max_instances = 1)
    {
        $this->servers[] = array($app_class, $middlewares, $protocol, $socket, $min_instances, $max_instances);
    }

    public function go()
    {
        $is_parent = true;

        foreach ($this->servers as $server) {
            $app = new $server[0];

            foreach (array_reverse($server[1]) as $mw_name) {
                $mw_class = 'MFS_AppServer_Middleware_'.$mw_name;
                $app = new $mw_class($app);
            }

            $handler = new MFS_AppServer_DaemonicHandler($server[3], $server[2], 'Socket');

            for ($i = 0; $i < $server[4]; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die('could not fork');
                } elseif ($pid === 0) {
                    // we are the child
                    $is_parent = false;
                    try {
                        $handler->serve($app);
                    } catch (Exception $e) {
                    }
                    die();
                } else {
                    // parent-process, just continue
                }
            }
        }

        if ($is_parent) {
            // should be called one time for each child?
            pcntl_wait($status); //Protect against Zombie children
        }
    }
}
