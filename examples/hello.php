<?php

require_once('vendor/autoload.php');

use LedSign\Transport\Tcp;
use LedSign\Protocol\JetFile2;

$jetfile = new JetFile2();
$t = new Tcp('192.168.178.22');


$cmd = $jetfile->getTextCommand('{center}{p5}{s3}{bgblack}{amber}{%d-%m-%C}{nf}{%H}:{%M}:{%S}{nf}{red}test');
date_default_timezone_set('CET');
$t->sendCommand($cmd);

echo $cmd;