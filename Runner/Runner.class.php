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
        $_servers = array();

        foreach ($this->servers as $server) {
            $handler = new \MFS\AppServer\DaemonicHandler($server['socket'], $server['protocol'], $server['transport']);

            for ($i = 0; $i < $server['min-children']; $i++) {
                $pid = $this->startWorker($handler, $server['app']);

                // store, how we started child process
                // (so, that later we can restart it with same settings)
                $_servers[$pid] = array($handler, $server);
            }
        }

        // should be called one time for each child?
        $status = null;
        while (true) {
            $old_pid = pcntl_wait($status);

            if (0 === $old_pid) {
                echo "[no children]\n";
                return;
            }

            if (-1 === $old_pid) {
                echo "[pcntl_wait error]\n";
                return;
            }

            echo "[restarting child]\n";

            list($handler, $server) = $_servers[$old_pid];
            unset($_servers[$old_pid]);

            $pid = $this->startWorker($handler, $server['app']);
            $_servers[$pid] = array($handler, $server);
        }
    }

    public function startWorker($handler, $app)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('could not fork');
        } elseif ($pid === 0) {
            // we are the child
            $this->worker($handler, $app);
            die('worker died');
        }

        // This is PARENT process
        return $pid;
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
