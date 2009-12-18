<?php

namespace MFS\AppServer;

abstract class DaemonicHandler implements iHandler
{
    protected $protocol = null;
    private $transport = null;
    private $has_gc = true;

    public function __construct()
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicException("Daemonic Application should be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicException("Daemonic Application requires PHP 5.3.0+");

        // Checking for GarbageCollection patch
        if (false === function_exists('gc_enabled')) {
            $this->has_gc = false;
            $this->log("WARNING: This version of PHP is compiled without GC-support. Memory-leaks are possible!");
        } elseif (false === gc_enabled()) {
            gc_enable();
        }
        $this->log('Initialized Daemonic Handler');
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
        $this->log("DeInitialized Daemonic Application: ".get_class($this));
    }

    public function serve($app)
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->app = $app;

        $this->log('Serving '.(is_object($this->app) ? get_class($this->app) : $this->app).' app…');
        $this->log('Protocol '.get_class($this->protocol).' protocol…');
        $this->log('Transport '.get_class($this->transport).' transport…');
        $this->log("Entering runloop…");

        try {
            $this->transport->loop();
        } catch (\Exception $e) {
            $this->protocol->doneWithRequest();
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }

        $this->log("Left runloop…");
    }

    public function onRequest($stream)
    {
        $this->log("got request");

        if (false === $this->protocol->readRequest($stream)) {
            return;
        }

        $context = array(
            'env' => $this->protocol->getHeaders(),
            'stdin' => $this->protocol->getStdin(),
            'logger' => function($message) {
                echo $message."\n";
            }
        );

        $this->log("-> calling handler");

        $result = call_user_func($this->app, $context);

        if (!is_array($result) or count($result) != 3)
            throw new BadProtocolException("App did not return proper result");

        $this->writeResponse($result);

        // cleanup
        unset($result);

        $this->protocol->doneWithRequest();
        $this->log("-> done with request");

        if (true === $this->has_gc) {
            gc_collect_cycles();
        }
    }

    abstract protected function writeResponse($response_data);

    public function log($message)
    {
        echo $message."\n";
    }
}
