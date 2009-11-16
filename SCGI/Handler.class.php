<?php

namespace MFS\AppServer\SCGI;

use MFS\AppServer\HTTP as HTTP;
use MFS\SCGI\Server as Server;

class Handler implements \MFS\AppServer\iHandler
{
    private $socket = null;
    private $has_gc = true;

    public function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicException("SCGI Application should be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicException("SCGI Application requires PHP 5.3.0+");

        // Checking for GarbageCollection patch
        if (false === function_exists('gc_enabled')) {
            $this->has_gc = false;
            $this->log("WARNING: This version of PHP is compiled without GC-support. Memory-leaks are possible!");
        } elseif (false === gc_enabled()) {
            gc_enable();
        }

        $this->protocol = new Server($socket_url);
        $this->log('Initialized SCGI Handler @ ['.$socket_url."]");
    }

    public function __destruct()
    {
        unset($this->protocol);
        $this->log("DeInitialized SCGI Application: ".get_class($this));
    }

    public function serve($app)
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->log('Serving '.(is_object($app) ? get_class($app) : $app).' app…');
        $this->log("Entering runloop…");

        try {
            while ($this->protocol->readRequest()) {
                $this->log("got request");
                $request = HTTP\Request::factory($this->protocol->getHeaders(), $this->protocol->getBody());
                $response = new Response($this->protocol, $request);

                $this->log("-> calling handler");
                $app(array('request' => $request, 'response' => $response));

                // cleanup
                unset($request);
                unset($response);

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

    public function log($message)
    {
        echo $message."\n";
    }
}
