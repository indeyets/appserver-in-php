<?php
require 'SCGI/autoload.php';

class SCGI_GraphApp extends MFS::AppServer::SCGI::Application
{
    public function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        parent::__construct($socket_url);
    }

    protected function requestHandler()
    {
        ob_start();
        GraphApp::main();
        $this->response()->write(ob_get_clean());
        $this->response()->write('<hr>Peak memory usage: '.number_format(memory_get_peak_usage()).' bytes');
    }
}
