<?php
namespace AiP\Transport;

abstract class AbstractTransport implements \AiP\Transport
{
    protected $addr;
    protected $callback;

    public function __construct($addr, $callback)
    {
        if (!is_callable($callback))
            throw new InvalidArgumentException('not a valid callback');

        $this->addr = $addr;
        $this->callback = $callback;

        $this->addSocket($this->addr);
    }

    abstract protected function addSocket($addr);

    public static function log($object, $message)
    {
        echo $object.' -> '.$message."\n";
    }
}
