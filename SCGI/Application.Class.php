<?php
namespace MFS\AppServer\SCGI;

use MFS\AppServer\HTTP as HTTP;
use MFS\SCGI\Server as Server;

class Application
{
    private $socket = null;
    private $request = null;
    private $response = null;
    private $has_gc = true;

    protected function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicException("SCGI Application should be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicException("SCGI Application requires PHP 5.3.0+");

        if (!extension_loaded('spl'))
            throw new LogicException("SCGI Application requires PHP compiled with SPL support");

        // Checking for GarbageCollection patch
        if (false === function_exists('gc_enabled')) {
            $this->has_gc = false;
            $this->log("WARNING: This version of PHP is compiled without GC-support. Memory-leaks are possible!");
        } elseif (false === gc_enabled()) {
            gc_enable();
        }

        $this->protocol = new Server($socket_url);
        $this->log('Initialized SCGI Application: '.get_class($this).' @ ['.$socket_url."]");
    }

    public function __destruct()
    {
        $this->log("DeInitialized SCGI Application: ".get_class($this));
    }

    final public function runLoop()
    {
        $this->log("Entering runloop…");

        try {
            while ($this->protocol->readRequest()) {
                $this->log("got request");
                $this->request = HTTP\Request::factory($this->protocol->getHeaders(), $this->protocol->getBody());
                $this->response = new Response($this->protocol, $this->request);

                $this->log("-> calling handler");
                $this->requestHandler();

                // cleanup
                unset($this->request);
                unset($this->response);
                $this->request = null;
                $this->response = null;

                $this->protocol->doneWithRequest();
                $this->log("-> done with request");

                if (true === $this->has_gc) {
                    gc_collect_cycles();
                }
            }
        } catch (\Exception $e) {
            $this->protocol->doneWithRequest();
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }


        $this->log("Left runloop…");
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
        echo $message."\n";
    }
}
