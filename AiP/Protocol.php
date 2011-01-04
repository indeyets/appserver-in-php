<?php

namespace AiP;

interface Protocol
{
    public function readRequest($stream);
    public function doneWithRequest();
    public function getHeaders();
    public function getStdin();
    public function write($data);
}
