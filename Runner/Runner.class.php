<?php

namespace MFS\AppServer\Runner;

declare(ticks=1);

class Runner
{
    private $servers;
    private $cwd;

    private $kids = array();

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
                $pid = $this->startWorker($handler, $server['app']);

                // store, how we started child process
                // (so, that later we can restart it with same settings)
                $this->kids[$pid] = array($handler, $server['app']);
            }
        }

        while (true) {
            pcntl_signal(SIGTERM, array($this, 'sigterm'), false);
            pcntl_signal(SIGINT,  array($this, 'sigterm'), false);
            pcntl_signal(SIGHUP,  array($this, 'sighup'),  false);

            $status = null;
            $old_pid = pcntl_wait($status);

            if (0 === $old_pid) {
                echo "[no workers]\n";
                return;
            }

            if (-1 === $old_pid) {
                continue; // signal arrived
            }

            echo "[Restarting Worker]\n";
            pcntl_signal(SIGTERM, SIG_DFL);
            pcntl_signal(SIGINT,  SIG_DFL);
            pcntl_signal(SIGHUP,  SIG_DFL);

            list($handler, $app) = $this->kids[$old_pid];
            unset($this->kids[$old_pid]);

            $pid = $this->startWorker($handler, $app);
            $this->kids[$pid] = array($handler, $app);
        }
    }

    protected function startWorker($handler, $app)
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

    protected function worker($handler, $app_data)
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
            pcntl_signal(SIGUSR1, array($handler, 'graceful'), false);
            $handler->serve($app);
            pcntl_signal(SIGUSR1,  SIG_DFL);
        } catch (\Exception $e) {
        }
    }


    protected function sigterm($signo)
    {
        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGHUP,  SIG_DFL);

        echo "\n[Stopping Workers]\n";
        foreach ($this->kids as $pid => $data) {
            posix_kill($pid, SIGTERM);
            unset($this->kids[$pid]);
        }

        die();
    }

    protected function sighup($signo)
    {
        echo "[Reload requested]\n";

        foreach ($this->kids as $pid => $data) {
            posix_kill($pid, SIGUSR1);
        }
    }
}
