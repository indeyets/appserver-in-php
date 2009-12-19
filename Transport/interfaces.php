<?php

interface MFS_AppServer_Transport_iTransport
{
    public function loop();
    public function unloop();
}
