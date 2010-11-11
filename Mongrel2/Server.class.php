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

    private $stream_name = null;
    private $stream = null;

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
        $_headers = json_decode($hd[0], true);

        $rest = $hd[1];
        $hd = self::parse_netstring($rest);
        $this->body = $hd[0];

        $this->headers = $this->processHeaders($_headers);
    }

    private function processHeaders($input)
    {
        $headers['SERVER_SOFTWARE'] = 'appserver-in-php';
        $headers['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $headers['SCRIPT_NAME'] = '';
        $headers['CONTENT_LENGTH'] = strlen($this->body);

        if (isset($input['content-type'])) {
            $headers['CONTENT_TYPE'] = $input['content-type'];
            unset($input['content-type']);
        }

        $headers['REQUEST_METHOD'] = $input['METHOD'];
        unset($input['METHOD']);

        if (isset($input['QUERY'])) {
            $headers['QUERY_STRING'] = $input['QUERY'];
            unset($input['QUERY']);
        }
        $headers['PATH_INFO'] = $input['PATH'];
        $headers['REQUEST_URI'] = $input['URI'];
        unset($input['PATH'], $input['URI']);

        if (false === $pos = strpos($input['host'], ':')) {
            $host = $input['host'];
            $port = 80;
        } else {
            $host = substr($input['host'], 0, $pos);
            $port = substr($input['host'], $pos + 1);
        }

        $headers['SERVER_NAME'] = $host;
        $headers['SERVER_PORT'] = strval($port);

        foreach ($input as $k => $v) {
            $headers['HTTP_'.strtoupper(str_replace('-', '_', $k))] = $v;
        }

        ksort($headers);

        return $headers;
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

        if (null !== $this->stream_name) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
                $this->stream = null;
            }
            \MFS\AppServer\StringStreamKeeper::cleanup($this->stream_name);
            $this->stream_name = null;
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStdin()
    {
        if (null === $this->stream_name) {
            $this->stream_name = \MFS\AppServer\StringStreamKeeper::keep($this->body);
            $this->stream = fopen($this->stream_name, 'r');
        }

        return $this->stream;
    }

    public function write($data)
    {
        $header = sprintf('%s %d:%s,', $this->sender, strlen($this->conn_id), $this->conn_id);
        $this->response->send($header.' '.$data);
    }
}
