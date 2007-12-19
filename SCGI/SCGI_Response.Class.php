<?php
namespace MFS::AppServer::SCGI;

class Response
{
    private $conn = null;

    private $headers = array();
    private $sent_headers = false;

    private $content_type = null;
    private $status = '200 Ok';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->content_type = ini_get('default_mimetype');

        if ($charset = ini_get('default_charset')) {
            $this->content_type .= '; charset='.$charset;
        }
    }

    public function addHeader($name, $value)
    {
        if ($this->sent_headers)
            throw new RuntimeException("headers are already sent");

        if ($name == 'Status') {
            $this->status = $value;
        } elseif ($name == 'Content-type') {
            $this->content_type = $value;
        } else {
            $this->headers[] = $name.': '.$value;
        }
    }

    public function write($string)
    {
        if (!$this->sent_headers) {
            $this->sendHeaders();
        }

        fwrite($this->conn, $string);
    }

    private function sendHeaders()
    {
        fwrite($this->conn, 'Status: '.$this->status."\r\n");
        fwrite($this->conn, 'Content-type: '.$this->content_type."\r\n");
        fwrite($this->conn, implode("\r\n", $this->headers));
        fwrite($this->conn, "\r\n\r\n");

        $this->sent_headers = true;
    }
}
