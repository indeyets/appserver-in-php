<?php
namespace MFS\AppServer\Transport;

interface iTransport
{
    public function loop();
    public function unloop();
}
