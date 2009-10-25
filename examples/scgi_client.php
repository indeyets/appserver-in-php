<?php

require __DIR__.'/../SCGI/autoload.php';

$client = new \MFS\SCGI\Client('tcp://127.0.0.1:9999');
$req = new \MFS\SCGI\ClientRequest($client);

$req->setURI('/');
$req->setMethod('GET');
var_dump($req->send());

$req->setMethod('POST');
$req->addPostParameter('abc', 'def');
$req->addPostParameter('ghi', 'jkl');

var_dump($req->send());
