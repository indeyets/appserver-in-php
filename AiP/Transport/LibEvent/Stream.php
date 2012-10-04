<?php

namespace AiP\Transport\LibEvent;

class Stream
{
    public static $transport;

    protected $conn_id;
    protected $last_readed_length = 1;
    protected $position = 0;

    public static function setTransport(\AiP\Transport\LibEvent $transport)
    {
        self::$transport = $transport;
    }

    /**
     * @return \AiP\Transport\LibEvent
     */
    public static function getTransport()
    {
        return self::$transport;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->conn_id = $url["host"];

        return true;
    }

    public function stream_read($count)
    {
        $readed = self::getTransport()->readFromBuffer($this->conn_id, $count);
        $this->last_readed_length = strlen($readed);
        $this->position += $this->last_readed_length;

        return $readed;
    }

    public function stream_write($data)
    {
        if(!self::getTransport()->writeToBuffer($this->conn_id, $data))
            throw new RuntimeException('Error on write to buffer');
    }

    public function stream_tell()
    {
    }

    public function stream_eof()
    {
        return 0 == $this->last_readed_length;
    }

    public function stream_seek($offset, $whence)
    {
    }
}

stream_wrapper_register("libevent-buffer", __NAMESPACE__."\Stream");
