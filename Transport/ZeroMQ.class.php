<?php
namespace MFS\AppServer\Transport;

class ZeroMQ extends BaseTransport
{
    private $pieces = null;

    protected $reqs = null;
    protected $resp = null;
    protected $in_loop = false;

    public function loop()
    {
        list($sender_id, $sub_addr, $pub_addr) = $this->pieces;

        $ctx = new \ZMQContext();

        $this->reqs = $ctx->getSocket(\ZMQ::SOCKET_UPSTREAM);
        $this->reqs->connect($sub_addr);

        $this->resp = $ctx->getSocket(\ZMQ::SOCKET_PUB);
        $this->resp->connect($pub_addr);
        $this->resp->setSockOpt(\ZMQ::SOCKOPT_IDENTITY, $sender_id);

        $this->in_loop = true;
        while ($this->in_loop) {
            $message = $this->reqs->recv();
            // self::log('ZeroMQ', 'received message');

            // self::log('ZeroMQ', 'callback begin');
            call_user_func($this->callback, array($message, $this->resp));
            // self::log('ZeroMQ', 'callback end');
        }
    }

    public function unloop()
    {
        $this->in_loop = false;
    }

    protected function addSocket($pieces)
    {
        $this->pieces = $pieces;
    }
}
