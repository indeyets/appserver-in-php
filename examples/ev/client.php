<?php

require dirname(__FILE__).'/../../autoload.php';

$client = new MFS_AppServer_SCGI_Client('tcp://127.0.0.1:9999');
$req = new MFS_AppServer_SCGI_ClientRequest($client);

$req->setURI('/?foo=42');
$req->setMethod('POST');
$req->addPostParameter('bar', 'baz');

echo $req->send()->body;
