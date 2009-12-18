<?php

class MFS_AppServer_SCGI_Handler extends MFS_AppServer_DaemonicHandler
{
    public function __construct($socket_url = 'tcp://127.0.0.1:9999', $transport_name = 'Socket')
    {
        parent::__construct();

        $transport_class = 'MFS_AppServer_Transport_'.$transport_name;
        $this->setTransport(new $transport_class($socket_url, array($this, 'onRequest')));
        $this->setProtocol(new MFS_AppServer_SCGI_Server());
    }

    protected function writeResponse($response_data)
    {
        $response = new MFS_AppServer_SCGI_Response($this->protocol);
        $response->setStatus($response_data[0]);
        for ($i = 0, $cnt = count($response_data[1]); $i < $cnt; $i++) {
            $response->addHeader($response_data[1][$i], $response_data[1][++$i]);
        }

        $response->sendHeaders();
        $this->protocol->write($response_data[2]); // body
    }
}
