<?php

require __DIR__.'/../AiP/autoload.php';

$client = new \AiP\Protocol\SCGI\Client('tcp://127.0.0.1:9999');
$req = new \AiP\Protocol\SCGI\ClientRequest($client);

$req->setURI('/');
$req->setMethod('GET');
var_dump($req->send());

$req->setMethod('POST');
$req->addPostParameter('abc', 'def');
$req->addPostParameter('ghi', 'jkl');

var_dump($req->send());
