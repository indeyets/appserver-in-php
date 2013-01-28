<?php

namespace AiP\Protocol;

use AiP\Transport\NoStreamException;

class HTTP implements \AiP\Protocol
{
    // iProtocol
    private $stream = null;
    private $headers = null;

    public function writeResponse($response_data)
    {
        $response = 'HTTP/1.0 '.$response_data[0]."\r\n";

        $server_set = false;
        for ($i = 0, $cnt = count($response_data[1]); $i < $cnt; $i++) {
            if ($response_data[1][$i] === 'Server') {
                $server_set = true;
            }
            $response .= $response_data[1][$i].': '.$response_data[1][++$i]."\r\n";
        }

        if (false === $server_set) {
            $response .= 'Server: AiP (http://github.com/indeyets/appserver-in-php)'."\r\n";
        }

        $response .= "\r\n";

        // reponse is string
        if (is_string($response_data[2])) {
            $response .= $response_data[2];
        }

        try {
            $this->write($response); // body

            // response is stream
            if (is_resource($response_data[2])) {
                fseek($response_data[2], 0);

                while (!feof($response_data[2])) {
                    $this->write(fread($response_data[2], 1024));
                }
                fclose($response_data[2]);
            }
        } catch (NoStreamException $e) {
            if (is_resource($response_data[2])) {
                fclose($response_data[2]);
            }

            throw $e;
        }
    }

    public function readRequest($stream, $remote_addr)
    {
        $this->stream = $stream;

        do {
            $_headers_str = stream_get_line($this->stream, 65535, "\r\n\r\n");

            if ('' === $_headers_str) {
                // client just disconnected
                return false;
            }
        } while (false === $_headers_str);

        if (extension_loaded('httpparser')) {
            $parser = new \HttpParser();
            $parser->execute($_headers_str."\r\n", 0);
            $this->headers = $parser->getEnvironment();
            unset($parser);
        } else {
            // native parsing
            // TODO: implement support for multiline headers (see http-spec for details)

            $_headers = explode("\r\n", $_headers_str); // getting headers

            list($http_method, $url, $http_version) = sscanf(array_shift($_headers), "%s %s %s");

            $this->headers = array();
            foreach ($_headers as $element) {
                $divider = strpos($element, ': ');

                $name = 'HTTP_'.str_replace('-', '_', strtoupper(substr($element, 0, $divider)));
                $value = substr($element, $divider + 2);

                $this->headers[$name] = $value;

            }
            unset($_headers, $first);

            $this->headers['HTTP_VERSION'] = $http_version;
            $this->headers['REQUEST_METHOD'] = $http_method;
            $this->headers['REQUEST_URI'] = $url;

            if (false === $pos = strpos($url, '?')) {
                $this->headers['PATH_INFO'] = $url;
                $this->headers['QUERY_STRING'] = '';
            } else {
                $this->headers['PATH_INFO'] = substr($url, 0, $pos);
                $this->headers['QUERY_STRING'] = strval(substr($url, $pos + 1));
            }
        }

        $this->headers['SERVER_SOFTWARE'] = 'appserver-in-php';
        $this->headers['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $this->headers['SCRIPT_NAME'] = '';

        if (null !== $remote_addr) {
            $pos = strrpos($remote_addr, ':');
            $this->headers['REMOTE_ADDR'] = substr($remote_addr, 0, $pos);
            $this->headers['REMOTE_PORT'] = substr($remote_addr, $pos + 1);
        }

        if (isset($this->headers['HTTP_HOST'])) {
            if (false === $pos = strpos($this->headers['HTTP_HOST'], ':')) {
                $host = $this->headers['HTTP_HOST'];
                $port = 80;
            } else {
                $host = substr($this->headers['HTTP_HOST'], 0, $pos);
                $port = substr($this->headers['HTTP_HOST'], $pos + 1);
            }

            $this->headers['SERVER_NAME'] = $host;
            $this->headers['SERVER_PORT'] = strval($port);
        } else {
            $this->headers['SERVER_NAME'] = 'localhost';
            $this->headers['SERVER_PORT'] = '80';
        }

        if (isset($this->headers['HTTP_CONTENT_TYPE'])) {
            $this->headers['CONTENT_TYPE'] = $this->headers['HTTP_CONTENT_TYPE'];
            unset($this->headers['HTTP_CONTENT_TYPE']);
        }

        if (isset($this->headers['HTTP_CONTENT_LENGTH'])) {
            $this->headers['CONTENT_LENGTH'] = $this->headers['HTTP_CONTENT_LENGTH'];
            unset($this->headers['HTTP_CONTENT_LENGTH']);
        } else {
            $this->headers['CONTENT_LENGTH'] = 0;
        }

        ksort($this->headers);
    }

    public function doneWithRequest()
    {
        if (null !== $this->stream) {
            $this->headers = null;

            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStdin()
    {
        return $this->stream;
    }

    public function write($data)
    {
        $retval = @fwrite($this->stream, $data);

        if (false === $retval) {
            throw new NoStreamException();
        }
    }
}
