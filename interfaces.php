<?php

namespace MFS\AppServer;

interface iHandler
{
    public function serve($app);
}

interface iProtocol
{
    public function readRequest($stream);
    public function doneWithRequest();
    public function getHeaders();
    public function getStdin();
    public function write($data);
}
