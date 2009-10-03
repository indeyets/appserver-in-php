<?php

ini_set('display_errors', 'Off');

require __DIR__.'/MyApp.class.php';

$app = new MyApp();
$app->runLoop();
