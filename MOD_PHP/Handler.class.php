<?php

class MFS_AppServer_MOD_PHP_Handler implements MFS_AppServer_iHandler
{
    private $socket = null;
    private $has_gc = true;

    public function __construct()
    {
        if (PHP_SAPI === 'cli')
            throw new LogicException("MOD_PHP Application should not be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicException("Application requires PHP 5.3.0+");
    }

    public function serve($app)
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->log('Serving '.(is_object($app) ? get_class($app) : $app).' app…');

        try {
            $this->log("got request");

            $context = array(
                'env' => $_SERVER,
                'stdin' => fopen("php://input", "r"),
                'logger' => function($message) {
                    trigger_error($message, E_USER_NOTICE);
                },
                '_GET' => $_GET,
                '_POST' => $_POST,
                '_FILES' => $_FILES,
                '_COOKIE' => new MFS_AppServer_MOD_PHP_Cookies(),
            );

            $result = call_user_func($app, $context);

            // headers
            $response = new MFS_AppServer_MOD_PHP_Response();
            $response->setStatus($result[0]);
            for ($i = 0, $cnt = count($result[1]); $i < $cnt; $i++) {
                $response->addHeader($result[1][$i], $result[1][++$i]);
            }
            unset($response);

            // body
            if (is_string($result[2])) {
                echo $result[2];
            } elseif (is_resource($result[2])) {
                while(!feof($result[2])) {
                    $this->write(fread($result[2], 1024));
                }
                fclose($result[2]);
            }
            unset($result);

            $this->log("-> done with request");
        } catch (Exception $e) {
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }
    }
    public function log($message)
    {
        trigger_error($message, E_USER_NOTICE);
    }
}
