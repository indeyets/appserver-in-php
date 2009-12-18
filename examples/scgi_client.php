<?php

require dirname(__FILE__).'/../../autoload.php';

$client = new MFS_AppServer_SCGI_Client('tcp://127.0.0.1:9999');
$req = new MFS_AppServer_SCGI_ClientRequest($client);

$req->setURI('/');
$req->setMethod('GET');
var_dump($req->send());

$req->setMethod('POST');
$req->addPostParameter('abc', 'def');
$req->addPostParameter('ghi', 'jkl');

var_dump($req->send());
