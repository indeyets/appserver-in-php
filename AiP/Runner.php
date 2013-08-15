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
                $this->startWorker($handler, $server['app']);
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

            $this->restartWorker($old_pid);
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
            posix_setuid(self::getUserId($server['user']));
        }

        if (isset($server['group'])) {
            posix_setgid(self::getGroupId($server['group']));
        }
    }

    protected function startWorker($handler, $app)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('could not fork');
        }

        if ($pid === 0) {
            // we are the child
            $this->worker($handler, $app);
            die('worker died');
        }

        // This is PARENT process

        // store, how we started child process
        // (so, that later we can restart it with same settings)
        $this->kids[$pid] = array($handler, $app);

        return $pid;
    }

    protected function restartWorker($old_pid)
    {
        list($handler, $app) = $this->kids[$old_pid];
        unset($this->kids[$old_pid]);

        $this->startWorker($handler, $app);
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


    /**
     * Converts user-name into UID
     * @param mixed $user_name
     * @return int
     * @throws \Exception
     */
    protected static function getUserId($user_name)
    {
        if (is_int($user_name)) {
            // this is a UID already
            return $user_name;
        }


        $info = posix_getpwnam($user_name);

        if ($info === false) {
            throw new \Exception('User '.$user_name.' is not available.');
        }

        return $info['uid'];
    }

    /**
     * Converts group-name into GID
     * @param mixed $group_name
     * @return int
     * @throws \Exception
     */
    protected static function getGroupId($group_name)
    {
        if (is_int($group_name)) {
            // this is a GID already
            return $group_name;
        }
        $info = posix_getgrnam($group_name);

        if ($info === false) {
            throw new \Exception('Group '.$group_name.' is not available.');
        }

        return $info['gid'];
    }
}
