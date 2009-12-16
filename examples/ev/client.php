<?php

require __DIR__.'/../../autoload.php';

$client = new \MFS\AppServer\SCGI\Client('tcp://127.0.0.1:9999');
$req = new \MFS\AppServer\SCGI\ClientRequest($client);

$req->setURI('/?foo=42');
$req->setMethod('POST');
$req->addPostParameter('bar', 'baz');

echo $req->send()->body;
