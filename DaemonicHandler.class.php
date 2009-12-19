<?php

namespace MFS\AppServer;

class DaemonicHandler implements iHandler
{
    protected $protocol = null;
    private $transport = null;

    public function __construct($socket_url, $protocol_name, $transport_name = 'Socket')
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicException("Daemonic Application should be run using CLI SAPI");

        if (version_compare("5.3.0", PHP_VERSION, '>'))
            throw new LogicException("Daemonic Application requires PHP 5.3.0+");

        // Checking for GarbageCollection patch
        if (false === gc_enabled()) {
            gc_enable();
        }

        $transport_class = 'MFS\\AppServer\\Transport\\'.$transport_name;
        $this->setTransport(new $transport_class($socket_url, array($this, 'onRequest')));
        $protocol_class = 'MFS\\AppServer\\'.$protocol_name.'\\Server';
        $this->setProtocol(new $protocol_class);

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
        $this->log("DeInitialized Application: ".get_class($this));
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

        $this->protocol->writeResponse($result);

        // cleanup
        unset($result);

        $this->protocol->doneWithRequest();
        $this->log("-> done with request");

        gc_collect_cycles();
    }

    public function log($message)
    {
        echo $message."\n";
    }
}
