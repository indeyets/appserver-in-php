<?php

namespace MFS\AppServer\Mongrel2;

class Server implements \MFS\AppServer\iProtocol
{
    // iProtocol
    private $response = null;

    private $sender = null;
    private $conn_id = null;
    private $path = null;

    private $headers = null;
    private $body = null;

    public function writeResponse($response_data)
    {
        $response = 'HTTP/1.0 '.$response_data[0]."\r\n";

        $len_set = false;
        for ($i = 0, $cnt = count($response_data[1]); $i < $cnt; $i++) {
            if ($response_data[1][$i] == 'Content-Length')
                $len_set = true;
            $response .= $response_data[1][$i].': '.$response_data[1][++$i]."\r\n";
        }

        if (is_string($response_data[2])) {
            $body = $response_data[2];
        } elseif (is_resource($response_data[2])) {
            $body = stream_get_contents($response_data[2]);
            fclose($response_data[2]);
        }

        if (!$len_set) {
            $response .= 'Content-length: '.strlen($body)."\r\n";
        }

        $response .= "\r\n";
        $response .= $body;

        $this->write($response); // body
    }

    public function readRequest($input)
    {
        list($message, $this->response) = $input;

        list($this->sender, $this->conn_id, $this->path, $rest) = explode(' ', $message, 4);

        $hd = self::parse_netstring($rest);
        $this->headers = json_decode($hd[0], true);

        $this->headers['REQUEST_METHOD'] = $this->headers['METHOD'];
        $this->headers['HTTP_VERSION'] = $this->headers['VERSION'];

        $url = $this->headers['REQUEST_URI'] = $this->headers['PATH'];
        if (false === $pos = strpos($url, '?')) {
            $this->headers['PATH_INFO'] = $url;
            $this->headers['QUERY_STRING'] = '';
        } else {
            $this->headers['PATH_INFO'] = substr($url, 0, $pos);
            $this->headers['QUERY_STRING'] = strval(substr($url, $pos + 1));
        }

        $this->headers['SERVER_SOFTWARE'] = 'appserver-in-php';
        $this->headers['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $this->headers['SCRIPT_NAME'] = '';

        if (false === $pos = strpos($this->headers['host'], ':')) {
            $host = $this->headers['host'];
            $port = 80;
        } else {
            $host = substr($this->headers['host'], 0, $pos);
            $port = substr($this->headers['host'], $pos + 1);
        }

        $this->headers['HTTP_HOST'] = $host.':'.$port;
        $this->headers['SERVER_NAME'] = $host;
        $this->headers['SERVER_PORT'] = strval($port);

        ksort($this->headers);

        $rest = $hd[1];
        $hd = self::parse_netstring($rest);
        $this->body = $hd[0];
    }

    public static function parse_netstring($ns)
    {
        list($len, $rest) = explode(':', $ns, 2);
        $len = intval($len);

        return array(
            substr($rest, 0, $len),
            substr($rest, $len+1)
        );
    }


    public function doneWithRequest()
    {
        $this->headers = null;
        $this->body = null;
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
        $header = sprintf('%s %d:%s,', $this->sender, strlen($this->conn_id), $this->conn_id);
        $this->response->send($header.' '.$data);
    }
}
