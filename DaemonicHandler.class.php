<?php

abstract class MFS_AppServer_DaemonicHandler implements MFS_AppServer_iHandler
{
    protected $protocol = null;
    private $transport = null;
    private $has_gc = false;

    public function __construct()
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicException("Daemonic Application should be run using CLI SAPI");

        if (version_compare("5.2.0", PHP_VERSION, '>'))
            throw new LogicException("Daemonic Application requires PHP 5.2.0+");

        if (version_compare("5.3.0", PHP_VERSION, '<=')) {
            // Advertising PHP-5.3
            $this->log("============================================================================");
            $this->log("WARNING: You use PHP-5.3, but this version of AppServer is a backport to 5.2");
            $this->log("         Use regular AppServer instead");
            $this->log("============================================================================");

            if (false === gc_enabled()) {
                gc_enable();
            }
            $this->has_gc = true;
        } else {
            // Ranting about Garbage Collection
            $this->log("============================================================================");
            $this->log("WARNING: PHP-5.2 does not have Garbage Collector. Memory-leaks are possible!");
            $this->log("         upgrade to PHP-5.3 if possible");
            $this->log("============================================================================");
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
        } catch (Exception $e) {
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
            'logger' => array($this, 'log'),
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
