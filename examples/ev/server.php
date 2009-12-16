<?php

require realpath(__DIR__.'/../..').'/autoload.php';

class DamnSmallServer
{
    protected $requests_counter = 0;

    public function __invoke($context)
    {
        $this->requests_counter++;
        $body = 'Request #'.$this->requests_counter."\n";
        $body .= 'Memory usage: '.memory_get_usage()."\n";
        $body .= var_export($context, true);
        return array(200, array(), $body);
    }
}
$handler = new MFS\AppServer\SCGI\Handler();
$handler->setProtocol(new MFS\SCGI\Server());
$handler->setTransport(new MFS\AppServer\Transport\LibEvent('tcp://127.0.0.1:9999'));
$app = new MFS\AppServer\Middleware\PHP_Compat\PHP_Compat(new DamnSmallServer);
$handler->serve($app);