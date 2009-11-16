<?php
namespace MFS\AppServer\MOD_PHP;

class Handler implements \MFS\AppServer\iHandler
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
            $request = Request::factory();
            $this->log("-> parsed request");
            $response = new Response($request);

            $app($request, $response);

            unset($request);
            unset($response);

            $this->log("-> done with request");
        } catch (\Exception $e) {
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }
    }
    public function log($message)
    {
        trigger_error($message, E_USER_NOTICE);
    }
}
