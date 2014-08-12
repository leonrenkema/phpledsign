<?php

require_once('vendor/autoload.php');

use LedSign\Transport\Tcp;
use LedSign\Protocol\JetFile2;

$jetfile = new JetFile2();
$t = new Tcp();


$cmd = $jetfile->getTextCommand('{p5}{s3}{bgblack}{amber}{%d-%m-%C}{nf}{%H}:{%M}:{%S}');
$t->sendCommand($cmd);
echo $cmd;
//
////use LedSign\LedSign;
//
//$ledSign = new LedSign('192.168.178.22');
////$ledSign->initialize();
////$ledSign->setText('{p0}{s6}{bgblack}{green}{moveLeftIn}{moveLeftOut}Lorem ipsum dolor sit amet, consectetur adipiscing elit.');
//
//$msg = '{p0}{s3}{bgblack}{green}{%R}{nf}{%m.%d.%C}{moveLeftIn}{moveLeftOut}{nf}{amber}DOW {red}-5 {amber}AEX {green}+5';
//
//$ledSign->setText($msg);