<?php

class SCGI_Application
{
    private $socket = null;
    private $request = null;
    private $response = null;

    protected function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicalException("SCGI Application should be run using CLI SAPI");

        if (version_compare("5.2.1", PHP_VERSION, '>'))
            throw new LogicalException("SCGI Application requires PHP 5.1.2+");

        if (!extension_loaded('spl'))
            throw new LogicalException("SCGI Application requires PHP compiled with SPL support");

        $errno = 0;
        $errstr = "";
        $this->socket = stream_socket_server($socket_url, $errno, $errstr);

        if (false === $this->socket) {
            throw new RuntimeException('Failed creating socker-server (URL: "'.$socket_url.'"): '.$errstr, $errno);
        }

        echo "Initialized SCGI_Application: ".get_class($this)."\n";
    }

    public function __destruct()
    {
        fclose($this->socket);
        echo "DeInitialized SCGI_Application: ".get_class($this)."\n";
    }

    public function runLoop()
    {
        echo "Entering runloop…\n";

        while ($conn = stream_socket_accept($this->socket, -1)) {
            try {
                $this->request = new SCGI_Request($conn);
                $this->response = new SCGI_Response($conn);

                $this->requestHandler();

                unset($this->request);
                unset($this->response);
            } catch (RuntimeException $e) {
                echo '[Exception] '.get_class($e).': '.$e->getMessage()."\n";
            }

            $this->request = null;
            $this->response = null;

            fclose($conn);
        }

        echo "Left runloop…\n";
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
}
