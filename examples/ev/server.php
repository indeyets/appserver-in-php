<?php

require dirname(__FILE__).'/../../autoload.php';

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
$handler = new MFS_AppServer_SCGI_Handler();
$handler->setProtocol(new MFS_AppServer_SCGI_Server());
$handler->setTransport(new MFS_AppServer_Transport_LibEvent('tcp://127.0.0.1:9999', array($handler, 'onRequest')));

$app = new MFS_AppServer_Middleware_PHP_Compat_PHP_Compat(new DamnSmallServer);
$handler->serve($app);
