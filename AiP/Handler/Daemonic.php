<?php

namespace AiP\Handler;

use AiP\Handler\Daemonic\LogicException;
use AiP\Handler\Daemonic\InvalidArgumentException;
use AiP\Handler\Daemonic\BadProtocolException;

class Daemonic implements \AiP\Handler
{
    private $in_request = false;
    private $should_stop = false;

    protected $protocol = null;
    private $transport = null;
    private $app = null;

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

        $transport_class = 'AiP\Transport\\'.$transport_name;
        $transport_obj = new $transport_class($socket_url, array($this, 'onRequest'));
        $this->setTransport($transport_obj);

        $protocol_class = 'AiP\Protocol\\'.$protocol_name;
        $protocol_obj = new $protocol_class();
        $this->setProtocol($protocol_obj);
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
    }

    public function serve($app)
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->app = null;
        $this->app = $app;

        try {
            $this->transport->loop();
        } catch (\Exception $e) {
            $this->protocol->doneWithRequest();
            $this->log('[Exception] '.get_class($e).': '.$e->getMessage());
        }
    }

    public function onRequest($stream, $remote_addr)
    {
        $this->in_request = true;

        if (false === $this->protocol->readRequest($stream, $remote_addr)) {
            return;
        }

        $context = array(
            'env' => $this->protocol->getHeaders(),
            'stdin' => $this->protocol->getStdin(),
            'logger' => function($message) {
                echo $message."\n";
            }
        );

        $result = call_user_func($this->app, $context);
        unset($context);

        if (!is_array($result) or count($result) != 3)
            throw new BadProtocolException("App did not return proper result");

        $this->protocol->writeResponse($result);

        // cleanup
        unset($result);

        $this->protocol->doneWithRequest();
        $this->in_request = false;

        gc_collect_cycles();

        if ($this->should_stop) {
            die();
        }
    }

    public function log($message)
    {
        echo $message."\n";
    }


    // signal handler
    public function graceful()
    {
        if ($this->in_request) {
            $this->should_stop = true;
            return;
        }

        die();
    }
}
