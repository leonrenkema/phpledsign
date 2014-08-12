<?

/*
 * Example of showing Facebook status updates of friends
 * Updates every two minutes.
 */

require_once('LedSign.php');
$ledSign = new LedSign('192.168.0.106'); // change this to the ip of your sign

// change this to the address of your friends rss feed
// found under "All friends"
$feedUrl = 'http://www.facebook.com/feeds/friends_status.php'
         . '?id=SOME_ID&key=SOME_KEY&format=rss20&flid=0';


while (true) {
		
	// Load the rss feed from Facebook
	// We need to use curl to fake the user agent, because otherwise Facebook
	// responds with an "unsupported browser" error
	$ch = curl_init();
	$userAgent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; fi; rv:1.9.0.4) ' 
	           . 'Gecko/2008102920 Firefox/3.0.4';
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_URL, $feedUrl);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$rss = curl_exec($ch);
	curl_close($ch);
	
	@$feed = new SimpleXMLElement($rss);
	
	$text = "{f0}";

	$count = 0;
	foreach ($feed->channel->item as $item){
		
		$title = (string)$item->title;
		
		// handle non-ASCII friends
		$title = str_replace('ä', 'a', $title);
		$title = str_replace('Ä', 'A', $title);
		$title = str_replace('ö', 'o', $title);
		$title = str_replace('Ö', 'O', $title);
		$title = LedSign::replaceCurlyBrackets($title);
		
		$text = $text . "$title  |  ";
		$count++;
		if ($count > 15){
			break;	
		}
	} 
	$ledSign->setText($text, true);
	sleep(120);
}

?>
