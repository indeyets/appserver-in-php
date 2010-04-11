<?php
namespace MFS\AppServer\Transport;

class Socket extends BaseTransport
{
    protected $socket = null;

    protected $connections = array();
    protected $connections_count = 0;

    protected $in_loop = false;

    public function loop()
    {
        $this->in_loop = true;
        while ($this->in_loop) {
            $conn = stream_socket_accept($this->socket, -1);
            self::log('Socket', 'accepted');
            // stream_set_blocking($socket, 0);

            self::log('Socket', 'callback begin');
            call_user_func($this->callback, $conn);
            self::log('Socket', 'callback end');
        }
    }

    public function unloop()
    {
        $this->in_loop = false;
    }

    protected function addSocket($addr)
    {
        var_dump('hello');
        $errno = 0;
        $errstr = '';
        $this->socket = stream_socket_server($addr, $errno, $errstr);

        if (false == $this->socket) {
            throw new RuntimeException("Can't create socket(".$errno."): ".$errstr);
        }
    }
}
