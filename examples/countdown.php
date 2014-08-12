<?php

/*
 * Simple countdown example. Counts down
 * from 10 and displays the text "GO!".
 * Very low network latency is assumed.
 */

require_once('LedSign.php');
$ledSign = new LedSign('192.168.178.22'); // change this to ip of your sign

for ($i = 10; $i > 0; $i--) {	
	sleep(1);
	$ledSign->setText($i);
}

$ledSign->setText('GO!');
