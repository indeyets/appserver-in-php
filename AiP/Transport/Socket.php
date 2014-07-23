<?php
namespace AiP\Transport;

use AiP\Transport\Socket\RuntimeException;

class Socket extends AbstractTransport
{
    protected $socket = null;

    protected $connections = array();
    protected $connections_count = 0;

    protected $in_loop = false;

    public function __construct($addr, $callback)
    {
        if (PHP_MAJOR_VERSION === 5 and PHP_MINOR_VERSION === 3 and in_array(PHP_RELEASE_VERSION, array(9, 10))) {
            throw new \LogicException('PHP 5.3.9 and 5.3.10 have bug in stream_get_line(). see https://bugs.php.net/bug.php?id=60817');
        } elseif (PHP_MAJOR_VERSION === 5 and PHP_MINOR_VERSION === 4 and PHP_RELEASE_VERSION <= 3) {
            throw new \LogicException('PHP versions earlier than 5.4.3 have bug in stream_get_line(). see https://bugs.php.net/bug.php?id=60817');
        }

        parent::__construct($addr, $callback);
    }

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
                if (1 === @stream_select($read, $write, $except, null)) {
                    $conn = @stream_socket_accept($this->socket, 0);
                }
            }

            if (false !== $conn) {
                $remote_addr = stream_socket_get_name($conn, true);

                if (false === $remote_addr) {
                    $remote_addr = null;
                }

                call_user_func($this->callback, $conn, $remote_addr);
            }

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
