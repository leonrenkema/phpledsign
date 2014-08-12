<?

/*
 * Example of showing titles of a digg.com RSS feed.
 * Updates every minute.
 */

require_once('LedSign.php');
$ledSign = new LedSign('192.168.0.106'); // change this to the ip of your sign

while (true) {
	
	$rss = file_get_contents('http://feeds.digg.com/digg/news/topic/world_news/popular.rss');
	@$feed = new SimpleXMLElement($rss);
	
	// set transitions
	$text = "";

	$count = 0;
	foreach ($feed->channel->item as $item){
		
		$title = (string)$item->title;
		$title = LedSign::replaceCurlyBrackets($title);
		
		$text = $text . "$title  |  ";
		$count++;
		if ($count > 20){
			break;	
		}
	} 
	
	$ledSign->setText($text, true);
	sleep(60);
}

?>
