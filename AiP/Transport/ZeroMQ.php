<?php
namespace AiP\Transport;

class ZeroMQ extends AbstractTransport
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
            declare(ticks=1) {
                $message = $this->reqs->recv();
            }
            call_user_func($this->callback, array($message, $this->resp));
            pcntl_signal_dispatch();
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
