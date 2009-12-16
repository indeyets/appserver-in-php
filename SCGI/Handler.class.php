<?php

namespace MFS\AppServer\SCGI;

use MFS\SCGI\Server as Server;

class Handler implements \MFS\AppServer\iHandler
{
    private $protocol = null;
    private $transport = null;
    private $has_gc = true;

    public function __construct()
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
        $this->log('Initialized SCGI Handler');
    }
    
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }
    
    public function setTransport($transport)
    {
        $this->transport = $transport;
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

        $app = \MFS\AppServer\callable($app);
        $this->app = $app;
        
        $this->log('Serving '.(is_object($this->app) ? get_class($this->app) : $this->app).' app…');
        $this->log('Protocol '.get_class($this->protocol).' protocol…');
        $this->log('Transport '.get_class($this->transport).' transport…');        
        $this->log("Entering runloop…");

        try {
            $this->transport->loop(\MFS\AppServer\callable(array($this, 'onRequest')));
        } catch (\Exception $e) {
            $this->protocol->doneWithRequest();
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }

        $this->log("Left runloop…");
    }
    
    public function onRequest($stream)
    {
        $this->log("got request");

        $this->protocol->readRequest($stream);

        $context = array(
            'env' => $this->protocol->getHeaders(),
            'stdin' => $this->protocol->getStdin(),
            'logger' => function($message) {
                echo $message."\n";
            }
        );

        $this->log("-> calling handler");
        
        $app = $this->app;
        $result = $app($context);

        if (!is_array($result) or count($result) != 3)
            throw new BadProtocolException("App did not return proper result");

        $response = new Response($this->protocol);
        $response->setStatus($result[0]);
        for ($i = 0, $cnt = count($result[1]); $i < $cnt; $i++) {
            $response->addHeader($result[1][$i], $result[1][++$i]);
        }

        $response->sendHeaders();
        $this->protocol->write($result[2]); // body

        // cleanup
        unset($response, $result);

        $this->protocol->doneWithRequest();
        $this->log("-> done with request");

        if (true === $this->has_gc) {
            gc_collect_cycles();
        }
    }

    public function log($message)
    {
        echo $message."\n";
    }
}
