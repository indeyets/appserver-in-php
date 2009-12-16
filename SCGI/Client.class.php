<?php
namespace MFS\AppServer\SCGI;

class Client
{
    private $url = null;
    private $socket = null;

    private $headers = null;
    private $body = null;

    public function __construct($socket_url)
    {
        $this->url = $socket_url;
    }

    public function sendRequest(array $headers, $body = null)
    {
        $errno = 0;
        $errstr = "";
        $this->socket = stream_socket_client($this->url, $errno, $errstr);

        if (false === $this->socket) {
            throw new RuntimeException('Failed creating socket-client (URL: "'.$socket_url.'"): '.$errstr, $errno);
        }

        // Setting required headers
        $headers[] = array('SCGI', '1');
        $headers[] = array('GATEWAY_INTERFACE', 'CGI/1.1');

        // Getting parts from REQUEST_URI
        foreach ($headers as $pair) {
            if ($pair[0] == 'REQUEST_URI') {
                $uri = $pair[1];
                break;
            }
        }

        if (!isset($uri)) {
            throw new LogicException("You can't send request without URI");
        }

        if (($pos = strpos($uri, '?')) !== false) {
            $headers[] = array('QUERY_STRING', substr($uri, $pos + 1));
            $uri = substr($uri, 0, $pos);
        } else {
            $headers[] = array('QUERY_STRING', '');
        }

        $headers[] = array('PATH_INFO', substr($uri, 1));
        $headers[] = array('SCRIPT_NAME', '/');

        if (null === $body)
            $headers[] = array('CONTENT_LENGTH', '0');
        else
            $headers[] = array('CONTENT_LENGTH', strlen($body));

        $headers_str = array_reduce(
            $headers,
            function($res, $item) {
                if ('' !== $res)
                    $res .= "\0";
                return $res.$item[0]."\0".$item[1];
            },
            ''
        );

        $this->write(strlen($headers_str).':');
        $this->write($headers_str.','.(null === $body ? '' : $body));


        $body = '';
        while (!feof($this->socket)) {
            $body .= fread($this->socket, 1024);
        }
        fclose($this->socket);

        list($headers_str, $this->body) = explode("\r\n\r\n", $body);
        $this->headers = array_map(
            function($item){
                return explode(': ', $item);
            },
            explode("\r\n", $headers_str)
        );
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    private function write($data)
    {
        fwrite($this->socket, $data);
    }
}
