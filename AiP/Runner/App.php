<?php

namespace AiP\Runner;

class App extends \pakeApp
{
    const VERSION = '0.4.0';

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

        pake_desc('Run server. usage: aip files [path/to/dir]');
        pake_task('MFS\AppServer\Runner\RunnerApp::files');
    }

    protected function runDefaultTask()
    {
        $this->display_tasks_and_comments();
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
            pake_echo_action('app+', $server['app']['class'].' server via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' workers)');
        }

        pake_echo_comment('Starting workers…');
        $runner->go();
    }

    public static function run_files($task, $args)
    {
        if (isset($args[0])) {
            if (!is_dir($args[0])) {
                throw new pakeException('"'.$args[0].'" is not a valid directory');
            }
            $path = realpath($args[0]);
        } else {
            $path = realpath('.');
        }

        $server = array(
            'protocol' => 'HTTP',
            'transport' => 'Socket',
            'socket' => 'tcp://127.0.0.1:8080',
            'min-children' => 1,
            'max-children' => 1,
            'app' => array(
                'class' => 'AiP\App\FileServe',
                'parameters' => array($path),
                'file' => '',
                'middlewares' => array(
                    'Logger',
                    array('class' => 'AiP\Middleware\Directory', 'parameters' => array($path, true)),
                    'ConditionalGet',
                ),
            ),
        );

        $runner = new Runner($path);

        $runner->addServer($server);
        pake_echo_action('app+', 'Serving files from "'.$path.'" via '.$server['protocol'].' at '.$server['socket'].'. ('.$server['min-children'].'-'.$server['max-children'].' workers)');

        pake_echo_comment('Starting workers…');
        $runner->go();
    }
}
