<?php
namespace MFS\AppServer\MOD_PHP;

class Application
{
    private $socket = null;
    private $request = null;
    private $response = null;
    private $has_gc = true;

    protected function __construct()
    {
        if (PHP_SAPI === 'cli')
            throw new LogicException("MOD_PHP Application should not be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicException("Application requires PHP 5.3.0+");

        if (!extension_loaded('spl'))
            throw new LogicException("Application requires PHP compiled with SPL support");
    }

    public function __destruct()
    {
    }

    final public function runLoop()
    {
        try {
            $this->log("got request");
            $this->request = Request::factory();
            $this->log("-> parsed request");
            $this->response = new Response($this->request);

            $this->requestHandler();

            unset($this->request);
            unset($this->response);
            $this->request = null;
            $this->response = null;

            $this->log("-> done with request");
        } catch (\Exception $e) {
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }
    }

    final protected function request()
    {
        return $this->request;
    }

    final protected function response()
    {
        return $this->response;
    }

    protected function requestHandler()
    {
        $this->response->addHeader('Status', '500 Internal Server Error');
        $this->response->addHeader('Content-type', 'text/html; charset=UTF-8');
        $this->response->write("<h1>500 — Internal Server Error</h1><p>Application doesn't implement requestHandler() method :-P</p>");
    }

    public function log($message)
    {
        trigger_error($message, E_USER_NOTICE);
    }
}
