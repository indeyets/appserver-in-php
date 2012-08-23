<?php
namespace AiP\Transport;

use AiP\Transport\Socket\RuntimeException;

class Socket extends AbstractTransport
{
    protected $socket = null;

    protected $connections = array();
    protected $connections_count = 0;

    protected $in_loop = false;

    public function loop()
    {
        $this->in_loop = true;

        while ($this->in_loop) {
            $conn = false;

            $read = array($this->socket);
            $write = null;
            $except = null;

            declare(ticks=1) {
                // stream_socket_accept() doesn't block on some(?) of the ARM systems
                // so, wrapping it into stream_select() which works always
                // see https://bugs.php.net/bug.php?id=62816
                if (1 === stream_select($read, $write, $except, null)) {
                    $conn = @stream_socket_accept($this->socket, 0);
                }
            }

            if (false === $conn)
                return;

            $remote_addr = stream_socket_get_name($conn, true);
            if (false === $remote_addr) {
                $remote_addr = null;
            }

            call_user_func($this->callback, $conn, $remote_addr);
            pcntl_signal_dispatch();
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
        $this->socket = stream_socket_server($addr, $errno, $errstr);

        if (false == $this->socket) {
            throw new RuntimeException("Can't create socket(".$errno."): ".$errstr);
        }
    }
}
