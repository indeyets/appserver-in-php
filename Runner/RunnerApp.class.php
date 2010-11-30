<?php

namespace MFS\AppServer\Runner;

class RunnerApp extends \pakeApp
{
    const VERSION = '0.2.2';

    protected function __construct()
    {
        parent::__construct();

        self::$EXEC_NAME = 'aip';
        self::$OPTIONS = array(
            // array('--interactive', '-i', \pakeGetopt::NO_ARGUMENT,       "Start aip in interactive (shell-like) mode."),
            array('--help',        '-H', \pakeGetopt::NO_ARGUMENT,       "Display this help message."),
            array('--usage',       '-h', \pakeGetopt::NO_ARGUMENT,       "Display usage."),
            array('--force-tty',   '',   \pakeGetopt::NO_ARGUMENT,       "Force coloured output"),
            array('--version',     '-V', \pakeGetopt::NO_ARGUMENT,       "Display the program version."),
        );
    }

    public static function get_instance()
    {
        if (!self::$instance)
            self::$instance = new RunnerApp();

        return self::$instance;
    }

    public function load_pakefile()
    {
        pake_desc('Run server. usage: aip app [config.yaml]');
        pake_task('MFS\AppServer\Runner\RunnerApp::app');
    }

    protected function runDefaultTask()
    {
        $this->display_tasks_and_comments();
    }


    public static function run_app($task, $args)
    {
        if (isset($args[0])) {
            $config_file = $args[0];

            if (is_dir($config_file)) {
                $config_file = realpath($config_file.'/config.yaml');
            }
        } else {
            $config_file = getcwd().'/config.yaml';
        }

        pake_echo_comment('Loading configuration…');

        if (!file_exists($config_file)) {
            throw new \pakeException("Configuration file is not found: ".$config_file);
        }

        $config = \pakeYaml::loadFile($config_file);

        $runner = new Runner(dirname($config_file));
        foreach ($config['servers'] as $server) {
            if (!isset($server['transport'])) {
                $server['transport'] = 'Socket';
            }

            $runner->addServer($server);
            pake_echo_action('register', $server['app']['class'].' server via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' children)');
        }

        pake_echo_comment('Starting server…');
        $runner->go();
    }

    public function showVersion()
    {
        parent::showVersion();
        echo sprintf('AiP  version %s', \pakeColor::colorize(self::VERSION, 'INFO'))."\n";
    }

    public function usage($hint_about_help = true)
    {
        echo ' '.self::$EXEC_NAME."             - to list commands\n";
        echo ' '.self::$EXEC_NAME." command     - to run specific command\n";

        if (true === $hint_about_help) {
            echo \pakeColor::colorize("Try ".self::$EXEC_NAME." -H for more information", 'INFO')."\n";
        }
    }
}
