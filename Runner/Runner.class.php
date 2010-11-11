<?php

namespace MFS\AppServer\Runner;

class Runner
{
    private $servers;

    public function __construct()
    {
        $this->servers = array();
    }

    public function addServer($app_class, array $middlewares, $protocol, $socket, $transport = 'Socket', $min_instances = 1, $max_instances = 1)
    {
        $this->servers[] = array(
            'app' => $app_class,
            'middlewares' => $middlewares,
            'protocol' => $protocol,
            'socket' => $socket,
            'transport' => $transport,
            'min_instances' => $min_instances,
            'max_instances' => $max_instances
        );
    }

    public function go()
    {
        $is_parent = true;

        foreach ($this->servers as $server) {
            $app = new $server['app'];

            foreach (array_reverse($server['middlewares']) as $mw_name) {
                $mw_class = 'MFS\AppServer\Middleware\\'.$mw_name.'\\'.$mw_name;
                $app = new $mw_class($app);
            }

            $handler = new \MFS\AppServer\DaemonicHandler($server['socket'], $server['protocol'], $server['transport']);

            for ($i = 0; $i < $server['min_instances']; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die('could not fork');
                } elseif ($pid === 0) {
                    // we are the child
                    $is_parent = false;
                    try {
                        $handler->serve($app);
                    } catch (\Exception $e) {
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
