<?php

namespace MFS\AppServer\Runner;

class Runner
{
    private $servers;
    private $cwd;

    public function __construct($cwd)
    {
        $this->cwd = $cwd;
        $this->servers = array();
    }

    public function addServer(array $server)
    {
        $this->servers[] = $server;
    }

    public function go()
    {
        $is_parent = true;

        foreach ($this->servers as $server) {
            $handler = new \MFS\AppServer\DaemonicHandler($server['socket'], $server['protocol'], $server['transport']);

            for ($i = 0; $i < $server['min-children']; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die('could not fork');
                } elseif ($pid === 0) {
                    // we are the child
                    $is_parent = false;

                    if (!class_exists($server['app']['class'])) {
                        require $this->cwd.'/'.$server['app']['file'];
                    }

                    $app = new $server['app']['class'];

                    foreach (array_reverse($server['app']['middlewares']) as $mw_name) {
                        $mw_class = 'MFS\AppServer\Middleware\\'.$mw_name.'\\'.$mw_name;
                        $app = new $mw_class($app);
                    }

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
            $status = null;
            pcntl_wait($status); //Protect against Zombie children
        }
    }
}
