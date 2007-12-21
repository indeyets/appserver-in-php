<?php
namespace MFS::AppServer::SCGI;
use MFS::AppServer::HTTP as HTTP;

class Application
{
    private $socket = null;
    private $request = null;
    private $response = null;
    private $has_gc = true;

    protected function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        if (PHP_SAPI !== 'cli')
            throw new LogicalException("SCGI Application should be run using CLI SAPI");

        if (version_compare("5.3.0-dev", PHP_VERSION, '>'))
            throw new LogicalException("SCGI Application requires PHP 5.3.0+");

        if (!extension_loaded('spl'))
            throw new LogicalException("SCGI Application requires PHP compiled with SPL support");

        // Checking for GarbageCollection patch
        if (false === function_exists('gc_enabled')) {
            $this->has_gc = false;
            echo "WARNING: This version of PHP is compiled without GC-support. Memory-leaks are possible!\n";
        } elseif (false === gc_enabled()) {
            gc_enable();
        }

        $errno = 0;
        $errstr = "";
        $this->socket = stream_socket_server($socket_url, $errno, $errstr);

        if (false === $this->socket) {
            throw new RuntimeException('Failed creating socket-server (URL: "'.$socket_url.'"): '.$errstr, $errno);
        }

        echo 'Initialized SCGI Application: '.get_class($this).' @ ['.$socket_url."]\n";
    }

    public function __destruct()
    {
        fclose($this->socket);
        echo "DeInitialized SCGI Application: ".get_class($this)."\n";
    }

    final public function runLoop()
    {
        echo "Entering runloop…\n";

        try {
            while ($conn = stream_socket_accept($this->socket, -1)) {
                $this->parseRequest($conn);
                $this->response = new Response($conn);

                $this->requestHandler();

                unset($this->request);
                unset($this->response);
                $this->request = null;
                $this->response = null;

                fclose($conn);
            }
        } catch (Exception $e) {
            fclose($conn);
            echo '[Exception] '.get_class($e).': '.$e->getMessage()."\n";
        }


        echo "Left runloop…\n";
    }

    private function parseRequest($conn)
    {
        $len = stream_get_line($conn, 20, ':');

        if (false === $len) {
            throw new Exception('error reading data');
        }

        if (!is_numeric($len)) {
            throw new Exception('invalid protocol ('.$len.')');
        }

        $_headers = explode("\0", stream_get_contents($conn, $len)); // getting headers
        $divider = stream_get_contents($conn, 1); // ","

        $headers = array();
        $first = null;
        foreach ($_headers as $element) {
            if (null === $first) {
                $first = $element;
            } else {
                $headers[$first] = $element;
                $first = null;
            }

            if (true === $this->has_gc) {
                gc_collect_cycles();
            }
        }
        unset($_headers, $first);

        if (!isset($headers['SCGI']) or $headers['SCGI'] != '1')
            throw new SCGI_Exception("Reqest is not SCGI/1 Compliant");

        if (!isset($headers['CONTENT_LENGTH']))
            throw new SCGI_Exception("CONTENT_LENGTH header not present");

        $body = ($headers['CONTENT_LENGTH'] > 0) ? stream_get_contents($conn, $headers['CONTENT_LENGTH']) : null;

        unset($headers['SCGI'], $headers['CONTENT_LENGTH']);

        $this->request = HTTP::Request::factory($headers, $body);
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
