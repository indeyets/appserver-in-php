<?php
namespace AiP;

interface Transport
{
    public function loop();
    public function unloop();
}
