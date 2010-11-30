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
        foreach ($this->servers as $server) {
            $handler = new \MFS\AppServer\DaemonicHandler($server['socket'], $server['protocol'], $server['transport']);

            for ($i = 0; $i < $server['min-children']; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die('could not fork');
                } elseif ($pid === 0) {
                    // we are the child
                    $this->worker($handler, $server['app']);
                    die('worker died');
                } else {
                    // parent-process, just continue
                }
            }
        }

        // should be called one time for each child?
        $status = null;
        pcntl_wait($status); //Protect against Zombie children
    }

    public function worker($handler, $app_data)
    {
        if (!class_exists($app_data['class'])) {
            require $this->cwd.'/'.$app_data['file'];
        }

        $app = new $app_data['class'];

        foreach (array_reverse($app_data['middlewares']) as $mw_name) {
            $mw_class = 'MFS\AppServer\Middleware\\'.$mw_name.'\\'.$mw_name;
            $app = new $mw_class($app);
        }

        try {
            $handler->serve($app);
        } catch (\Exception $e) {
        }
    }
}
