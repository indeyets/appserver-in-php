<?php
namespace MFS\AppServer\Transport;

abstract class BaseTransport
{
    protected $addrs;
    protected $callback;

    public function __construct($addrs, $callback)
    {
        if (!is_array($addrs))
            $addrs = array($addrs);

        $this->addrs = $addrs;
        $this->callback = $callback;
    }

    static function log($object, $object_id, $message)
    {
        echo "$object #{$object_id} -> $message\n";
    }

    abstract public function loop();
    abstract public function unloop();
}
