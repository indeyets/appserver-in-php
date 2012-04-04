<?php

namespace AiP;

interface Protocol
{
    public function writeResponse($response_data);
    public function readRequest($stream, $remote_addr);
    public function doneWithRequest();
    public function getHeaders();
    public function getStdin();
    public function write($data);
}
