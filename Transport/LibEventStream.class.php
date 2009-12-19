<?php

namespace MFS\AppServer\Transport;

class LibEventStream
{
    static $transport;

    protected $conn_id;
    protected $last_readed_length = 1;
    protected $position = 0;

    static function setTransport($transport)
    {
        self::$transport = $transport;
    }

    /**
     * @return LibEvent
     */
    static function getTransport()
    {
        return self::$transport;
    }

    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->conn_id = $url["host"];
        return true;
    }

    function stream_read($count)
    {
        $readed = self::getTransport()->readFromBuffer($this->conn_id, $count);
        $this->last_readed_length = strlen($readed);
        $this->position += $this->last_readed_length;
        return $readed;
    }

    function stream_write($data)
    {
        if(!self::getTransport()->writeToBuffer($this->conn_id, $data))
            throw new RuntimeException('Error on write to buffer');
    }

    function stream_tell()
    {
    }

    function stream_eof()
    {
        return 0 == $this->last_readed_length;
    }

    function stream_seek($offset, $whence)
    {
    }
}

stream_wrapper_register("libevent-buffer", __NAMESPACE__."\LibEventStream");
