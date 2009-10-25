<?php

require __DIR__.'/../SCGI/autoload.php';

$client = new \MFS\SCGI\Client('tcp://127.0.0.1:9999');

$client->sendRequest(
    array(
        array('REQUEST_METHOD', 'GET'),
        array('REQUEST_URI', '/')
    )
);

var_dump($client->getHeaders());
var_dump($client->getBody());

$client->sendRequest(
    array(
        array('REQUEST_METHOD', 'POST'),
        array('REQUEST_URI', '/')
    ),
    'abc=def&ghi=jkl'
);

var_dump($client->getHeaders());
var_dump($client->getBody());
