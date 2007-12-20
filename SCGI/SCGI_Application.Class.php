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

        if (false === function_exists('gc_enabled')) {
            $this->has_gc = false;
            echo "WARNING: This version of PHP is compiled without GC-support. Memory-leaks are possible!\n";
        } elseif (gc_enabled() === false) {
            gc_enable();
            echo "GC-support in PHP is enabled!\n";
        }

        $errno = 0;
        $errstr = "";
        $this->socket = stream_socket_server($socket_url, $errno, $errstr);

        if (false === $this->socket) {
            throw new RuntimeException('Failed creating socker-server (URL: "'.$socket_url.'"): '.$errstr, $errno);
        }

        echo 'Initialized SCGI Application: '.get_class($this).' @ ['.$socket_url."]\n";
    }

    public function __destruct()
    {
        fclose($this->socket);
        echo "DeInitialized SCGI Application: ".get_class($this)."\n";
    }

    public function runLoop()
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
        } catch (SCGI_Exception $e) {
            echo '[Exception] '.get_class($e).': '.$e->getMessage()."\n";
        }


        echo "Left runloop…\n";
    }

    private function parseRequest($conn)
    {
        if (!is_numeric($len = stream_get_line($conn, 20, ':')))
            throw new SCGI_Exception("invalid protocol");

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
