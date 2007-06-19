<?php

class SCGI_Response
{
    private $conn = null;

    private $headers = array();
    private $sent_headers = false;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addHeader($name, $value)
    {
        if ($this->sent_headers)
            throw new RuntimeException("headers are already sent");

        $this->headers[] = $name.': '.$value;
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
        fwrite($this->conn, implode("\r\n", $this->headers));
        fwrite($this->conn, "\r\n\r\n");

        $this->sent_headers = true;
    }
}