<?php

class MFS_AppServer_SCGI_Server implements MFS_AppServer_iProtocol
{
    private $stream = null;
    private $headers = null;

    public function __destruct()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
        // $this->log("DeInitialized SCGI Application: ".get_class($this));
    }

    public function readRequest($stream)
    {
        $this->stream = $stream;

        $len = stream_get_line($this->stream, 20, ':');

        if (false === $len) {
            throw new LogicException('error reading data');
        }

        if ('' === $len) {
            // could be bug in PHP or Lighttpd. sometimes, app just gets empty request
            // retrying
            $this->doneWithRequest();
            return false;
        }

        if (!is_numeric($len)) {
            throw new MFS_AppServer_SCGI_BadProtocolException('invalid protocol (expected length, got '.var_export($len, true).')');
        }

        $_headers_str = stream_get_contents($this->stream, $len);

        $_headers = explode("\0", $_headers_str); // getting headers
        $divider = stream_get_contents($this->stream, 1); // ","

        $this->headers = array();
        $first = null;
        foreach ($_headers as $element) {
            if (null === $first) {
                $first = $element;
            } else {
                $this->headers[$first] = $element;
                $first = null;
            }

        }
        unset($_headers, $first);

        if (!isset($this->headers['SCGI']) or $this->headers['SCGI'] != '1')
            throw new MFS_AppServer_SCGI_BadProtocolException("Request is not SCGI/1 Compliant (".var_dump($this->headers, true).")");

        if (!isset($this->headers['CONTENT_LENGTH']))
            throw new MFS_AppServer_SCGI_BadProtocolException("CONTENT_LENGTH header not present");

        unset($this->headers['SCGI']);
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
        fwrite($this->stream, $data);
    }
}
