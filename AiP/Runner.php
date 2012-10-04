<?php

namespace AiP;

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
            $handler = new \AiP\Handler\Daemonic($server['socket'], $server['protocol'], $server['transport']);

            // drop privileges after opening sockets, but before entering the working loop
            // this will only work when the process is started by root
            $this->dropPrivileges($server);

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
            declare(ticks=1) {
                $old_pid = pcntl_wait($status);
            }

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

    protected function dropPrivileges($server)
    {
        if (!array_key_exists('user', $server) and !array_key_exists('group', $server)) {
            // nothing to do
            return;
        }

        if (posix_getuid() != 0) {
            echo "\n[Warning] Can't change uid/gid because aip is not run by superuser\n";

            return;
        }

        if (isset($server['user'])) {
            posix_setuid($this->getUserId($server['user']));
        }

        if (isset($server['group'])) {
            posix_setgid($this->getGroupId($server['group']));
        }
    }

    protected function getUserId($user)
    {
        if (!is_int($user)) {
            $info = posix_getpwnam($user);

            if ($info === false) {
                throw new \Exception('User '.$user.' is not available.');
            }

            $user = $info['uid'];
        }

        return $user;
    }

    protected function getGroupId($group)
    {
        if (!is_int($group)) {
            $info = posix_getgrnam($group);

            if ($info === false) {
                throw new \Exception('Group '.$group.' is not available.');
            }

            $group = $info['gid'];
        }

        return $group;
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

        if (isset($app_data['parameters']) and count($app_data['parameters']) > 0) {
            $reflect  = new \ReflectionClass($app_data['class']);
            $app = $reflect->newInstanceArgs($app_data['parameters']);
        } else {
            $app = new $app_data['class'];
        }

        foreach (array_reverse($app_data['middlewares']) as $middleware) {
            if (is_array($middleware)) {
                $mw_class = $middleware['class'];
                $mw_params = array_merge(array($app), $middleware['parameters']);

                $reflect  = new \ReflectionClass($mw_class);
                $app = $reflect->newInstanceArgs($mw_params);
            } else {
                $mw_class = 'AiP\Middleware\\'.$middleware;
                $app = new $mw_class($app);
            }
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
