<?php

interface MFS_AppServer_iHandler
{
    public function serve($app);
}

interface MFS_AppServer_iProtocol
{
    public function readRequest($stream);
    public function doneWithRequest();
    public function getHeaders();
    public function getStdin();
    public function write($data);
}
