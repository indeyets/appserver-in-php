<?php

class MFS_AppServer_Runner_RunnerApp extends pakeApp
{
    public static function get_instance()
    {
        if (!self::$instance)
            self::$instance = new MFS_AppServer_Runner_RunnerApp();

        return self::$instance;
    }

    public function load_pakefile()
    {
        pake_desc('Run server. usage: aip app [config.yaml]');
        pake_task('MFS_AppServer_Runner_RunnerApp::app');
    }

    protected function runDefaultTask()
    {
        $this->display_tasks_and_comments();
    }


    public static function run_app($task, $args)
    {
        if (isset($args[0])) {
            $config_file = $args[0];
        } else {
            $config_file = getcwd().'/config.yaml';
        }

        if (!file_exists($config_file)) {
            throw new pakeException("Configuration file is not found: ".$config_file);
        }

        $config = pakeYaml::loadFile($config_file);

        $runner = new Runner();
        foreach ($config['servers'] as $server) {
            require_once dirname($config_file).'/'.$server['app']['file'];
            $runner->addServer($server['app']['class'], $server['app']['middlewares'], $server['protocol'], $server['socket'], $server['min-children'], $server['max-children']);
        }

        $runner->go();
    }
}
