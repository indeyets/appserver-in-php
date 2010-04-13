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

        pake_echo_comment('Loading configuration…');

        if (!file_exists($config_file)) {
            throw new pakeException("Configuration file is not found: ".$config_file);
        }

        $config = pakeYaml::loadFile($config_file);

        $runner = new Runner();
        foreach ($config['servers'] as $server) {
            if (!class_exists($server['app']['class'])) {
                require dirname($config_file).'/'.$server['app']['file'];
                pake_echo_action('load class', $server['app']['class']);
            }

            $runner->addServer($server['app']['class'], $server['app']['middlewares'], $server['protocol'], $server['socket'], $server['min-children'], $server['max-children']);
            pake_echo_action('register', $server['app']['class'].' server via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' children)');
        }

        pake_echo_comment('Starting server…');
        $runner->go();
    }
}
