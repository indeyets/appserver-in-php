<?php
namespace MFS\AppServer\Transport;

class Socket extends BaseTransport
{
    protected $sockets = array();
    protected $sockets_count = 0;

    protected $connections = array();
    protected $connections_count = 0;

    protected $in_loop = false;

    public function loop()
    {
        foreach ($this->addrs as $addr)
            $this->addSocket($addr);

        $this->in_loop = true;
        while ($this->in_loop) {
            foreach ($this->sockets as $socket_num => $socket) {
                $conn = stream_socket_accept($socket, -1);
                self::log('Socket', $socket_num, 'accepted');
                // stream_set_blocking($socket, 0);

                self::log('Socket', $socket_num, 'callback begin');
                call_user_func($this->callback, $conn);
                self::log('Socket', $socket_num, 'callback end');
            }
        }
    }

    public function unloop()
    {
        $this->in_loop = false;
    }

    protected function addSocket($addr)
    {
        $errno = 0;
        $errstr = '';
        $socket = stream_socket_server($addr, $errno, $errstr);
        if (false == $socket) {
            throw new Exception("Can't create socket(".$errno."): ".$errstr);
        }
        $socket_num = $this->sockets_count++;
        $this->sockets[$socket_num] = $socket;
        self::log('Socket', $socket_num, 'created');
    }
}
