<?php
namespace MFS\AppServer\Transport;

class LibEventUnbuffered extends BaseTransport
{
    protected $event_base;

    protected $sockets_count      = 0;
    protected $sockets            = array();
    protected $socket_events      = array();

    protected $callback;

    public function loop()
    {
        if (!$this->event_base = event_base_new())
            throw new RuntimeException("Can't create event base");

        foreach ($this->addrs as $addr) {
            $this->addAddr($addr);
        }

        event_base_loop($this->event_base);
    }

    public function unloop()
    {
       event_base_loopexit($this->event_base);
    }

    protected function addAddr($addr)
    {
        $socket_num = $this->addSocket($addr);
        $this->addSocketEvent($socket_num);
    }

    protected function addSocketEvent($socket_num)
    {
        $socket = $this->sockets[$socket_num];

        $event = event_new();
        if (!event_set($event, $socket, EV_READ | EV_PERSIST, array($this, 'onEventAccept'), array($socket_num))) {
            throw new RuntimeException("Can't set event");
        }

        if (false === event_base_set($event, $this->event_base))
            throw new RuntimeException("Can't set [{$socket_num}] event base.");

        if (false === event_add($event)) {
            throw new RuntimeException("Can't add event");
        }

        $this->socket_events[$socket_num] = $event;
        self::log('Socket', $socket_num, 'event added');
    }

    public function onEventAccept($socket, $event, $args)
    {
        $socket_num = $args[0];
        $conn = $this->acceptSocket($socket_num);

        self::log('Socket', $socket_num, 'callback');
        call_user_func($this->callback, $conn);
    }

    protected function addSocket($addr)
    {
        $socket = stream_socket_server($addr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        $socket_num = $this->sockets_count++;
        $this->sockets[$socket_num] = $socket;

        self::log('Socket', $socket_num, 'created on '.$addr);
        return $socket_num;
    }

    protected function acceptSocket($socket_num)
    {
        $socket = $this->sockets[$socket_num];
        $connection = stream_socket_accept($socket, 0);
        stream_set_blocking($socket, 0);

        self::log('Socket', $socket_num, 'accepted');
        return $connection;
    }
}
