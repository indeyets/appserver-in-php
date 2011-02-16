<?php
namespace AiP\Transport;

use AiP\Transport\LibEvent\RuntimeException;
use AiP\Transport\LibEvent\LogicException;

class LibEventUnbuffered extends AbstractTransport
{
    protected $event_base;

    protected $socket            = null;
    protected $socket_events      = array();

    protected $callback;

    public function __construct($addr, $callback)
    {
        if (!extension_loaded('libevent'))
            throw new LogicException('LibEvent transport requires pecl/libevent extension');

        parent::__construct($addr, $callback);
    }

    public function loop()
    {
        if (!$this->event_base = event_base_new())
            throw new RuntimeException("Can't create event base");

        $this->addSocketEvent();

        event_base_loop($this->event_base);
    }

    public function unloop()
    {
       event_base_loopexit($this->event_base);
    }

    protected function addSocketEvent()
    {
        $event = event_new();
        if (!event_set($event, $this->socket, EV_READ | EV_PERSIST, array($this, 'onEventAccept'))) {
            throw new RuntimeException("Can't set event");
        }

        if (false === event_base_set($event, $this->event_base))
            throw new RuntimeException("Can't set [{$socket_num}] event base.");

        if (false === event_add($event)) {
            throw new RuntimeException("Can't add event");
        }

        $this->socket_events = $event;
        self::log('Socket', 'event added');
    }

    public function onEventAccept($socket, $event)
    {
        $conn = $this->acceptSocket();

        $remote_addr = stream_socket_get_name($conn, true);
        if (false === $remote_addr) {
            $remote_addr = null;
        }

        self::log('Socket', 'callback');
        call_user_func($this->callback, $conn, $remote_addr);
    }

    protected function addSocket($addr)
    {
        $this->socket = stream_socket_server($addr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

        self::log('Socket', 'created on '.$addr);
    }

    protected function acceptSocket()
    {
        $connection = stream_socket_accept($this->socket, 0);
        stream_set_blocking($this->socket, 0);

        self::log('Socket', 'accepted');
        return $connection;
    }
}
