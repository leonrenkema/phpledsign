<?

/*
 * Example of showing numbers of users
 * online on a web site. Updates the count every 5 seconds.
 */

require_once('LedSign.php');
$ledSign = new LedSign('192.168.0.106'); // change this to ip of your sign

while (true) {
	// url to a script that returns the number
	// of users online in plain text
	$url = 'http://www.mywebcommunity/api/users-online';
	$i = file_get_contents($url);
	$ledSign->setText($i);		
	sleep(5);
}

?>
