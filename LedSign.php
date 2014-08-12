<?php

/*
 * Class for accessing compatible led signs
 *
 * 2008 (c) Juho Ojala
 * see LICENSE file for BSD license
 */
namespace LedSign;

class LedSign{	

	public $ip;
	public $port;

	function __construct($ip, $port = 9520){
		$this->sequence = 59;
		$this->ip = $ip;
		$this->port = $port;
	}
	
	/*
	 * Sets the text displayed on the Led Sign to
	 * $text. Accepts lot of special markup for changing
	 * colors, using transitions, multiple frames and so on.
	 */
	public function setText($text, $simpleMoveLeft = false) {
		$cmd = $this->getTextCommand($text, $simpleMoveLeft);
		return $this->sendCommand($cmd);
	}
	
	/*
	 * Initializes the Led Sign for use with the PHP library.
	 */
	public function initialize() {
		$cmd = $this->getSequentSysCommand();	
		$success = $this->sendCommand($cmd);
		$this->setText('init ok');
	}
	
	/*
	 * Replaces curly brackets with regular ones
	 */
	public function replaceCurlyBrackets($text) {
		$text = str_replace('{', '(', $text);
		$text = str_replace('}', ')', $text);		
		return $text;	
	}
	
	// The functions below are public only for
	// testing purposes. The actual inteface
	// consists solely of the functions above. 
	// ---------------------------------------
	
	
	/*
	 * Replaces special markup in $text with hex
	 * characters that the sign understands.
	 */
	public function parseText($text) {
		
		$replaceArray = array(		
			'{blink}' => chr(0x07) . '1',
			'{/blink}' => chr(0x07) . '0',
		
			'{%m/%d/%C}' => chr(0x0b) . chr(0x20), 
			'{%d/%m/%C}' => chr(0x0b) . chr(0x21), 
			'{%m-%d-%C}' => chr(0x0b) . chr(0x22), 
			'{%d-%m-%C}' => chr(0x0b) . chr(0x23), 
			'{%m.%d.%C}' => chr(0x0b) . chr(0x24), 

			'{%C}' => chr(0x0b) . chr(0x25),
			'{%Y}' => chr(0x0b) . chr(0x26),
			
			'{%m}' => chr(0x0b) . chr(0x27), // month as number
			'{%b}' => chr(0x0b) . chr(0x28), // abbreviated month name
			'{%d}' => chr(0x0b) . chr(0x29), // day 01-31

			'{%u}' => chr(0x0b) . chr(0x2a), // weekday as decimal
			'{%a}' => chr(0x0b) . chr(0x2b), // abbreviated weekday 
			
			'{%H}' => chr(0x0b) . chr(0x2C),
			'{%M}' => chr(0x0b) . chr(0x2D),
			'{%S}' => chr(0x0b) . chr(0x2E),	
		
			'{%R}' => chr(0x0b) . chr(0x2F), // time on 24 hour notation
			'{%r}' => chr(0x0b) . chr(0x30), // time in am/pm notation

			'{celsius}' => chr(0x0b) . chr(0x31), // temperature in celsius scale
			'{humidity}' => chr(0x0b) . chr(0x32), // humidity
			'{fahrenheit}' => chr(0x0b) . chr(0x33), // temperature in fahrenheit scale
			
			'{nf}' => chr(0x0c), // new frame
			'{nl}' => chr(0x0c), // new line

			'{left}' => chr(0x1e) . '1', // align left
			'{center}' => chr(0x1e) . '0', // center align
			'{right}' => chr(0x1e) . '2', // align right

			'{halfspace}' => chr(0x82), // half space
			
			'{red}' => chr(0x1c) . '1', // red
			'{green}' => chr(0x1c) . '2', // green
			'{amber}' => chr(0x1c) . '3', // amber/orange
                    
                    
			'{bgblack}' => chr(0x1d) . '0',
                
			'{mixed1}' => chr(0x1c) . '4', // amber-green-red pysty
			'{mixed2}' => chr(0x1c) . '5', // yellow-green-red
			'{mixed3}' => chr(0x1c) . '6', // 
			'{mixed4}' => chr(0x1c) . '7' // 
			
		);
		
		foreach ($replaceArray as $search => $replace) {
			$text = str_replace($search, $replace, $text);	
		}
		
		// font size
		preg_match_all('/{f(\d)}/', $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$text = str_replace($match[0], chr(0x1a) . $match[1], $text);	
		}
		
		// frame pause
		preg_match_all('/{p(\d\d?)}/', $text, $matches, PREG_SET_ORDER);
                
		foreach ($matches as $match) {
			if (strlen($match[1]) == 1) {
				$pauseTime = 0 . $match[1];	
			} else {
				$pauseTime = $match[1];	
			}
			$text = str_replace($match[0], chr(0x0e) . '0' . $pauseTime, $text);	
		}

		// display speed (speed of animation)
		preg_match_all('/{s(\d)}/', $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$text = str_replace($match[0], chr(0x0f) . $match[1], $text);	
		}

		// display modes (transitions)
		$modes = self::getDisplayModes();
		foreach ($modes as $key => $hexValue){
			$chars = chr(0x0a) . 'I' . $hexValue;
			$text = str_replace('{' . $key .'In}', $chars , $text);
		}
		foreach ($modes as $key => $hexValue){
			$chars = chr(0x0a) . 'O' . $hexValue;
			$text = str_replace('{' . $key .'Out}', $chars , $text);
		}
		
		preg_match_all('/{s(\d)}/', $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$text = str_replace($match[0], chr(0x0f) . $match[1], $text);	
		}

		return $text;
	}
	
	/*
	 * Returns the command for setting the text displayed
	 * on the sign to $text
	 */
	function getTextCommand($text, $simpleMoveLeft = false) {
	
		$commandsPrefix = chr(0x01) . "Z00" .chr(0x02). "A";		
		//$savePath = "A"; 
		$savePath = chr(0x0f) . "ETAB";
		$displayProtocol = chr(0x06);
		
		$displayMode = self::getDisplayModeBytes($simpleMoveLeft);		
		$colorSetting = self::getColorBytes('amber');

		$fontSize = chr(0x1a) . "1"; // 7x6, default
		$end = chr(0x04);
		
		$text = "{jumpOutOut}{jumpOutIn}" . $text;
		$text = self::parseText($text);
		
		$cmd = $commandsPrefix
		     . $savePath
		     . $displayProtocol
                     . $typeSetting
		     . $displayMode
		     . $fontSize
		     . $text
		     . $end;

		return $cmd;
	}
	
	function sendCommand($cmd) {
		
		$fp = fsockopen("tcp://{$this->ip}", $this->port, $errno, $errstr);
		stream_set_timeout($fp, 5);
                
                echo $cmd;
		if (!$fp) {
			echo "ERROR: $errno - $errstr<br />\n";
		} else {	
			fwrite($fp, $cmd);
                        
                        fclose($fp);
		}
		//echo "Response " . $response;
		//return $response == "OK";
		return;
	}

	function getColorBytes($color) {
		$colorNumbers['red'] = 1;
		$colorNumbers['green'] = 2;
		$colorNumbers['amber'] = 3; // orange
		
		$colorNumbers['mixed1'] = 4; // amber-green-red pystysuunnassa
		$colorNumbers['mixed2'] = 5; // yellow-green-red by merkki merkiltä
		$colorNumbers['mixed3'] = 6; // random parts
		$colorNumbers['mixed4'] = 7; // 
		
		return chr(0x1c) . $colorNumbers[$color];
	}

	function getDisplayModes() {
		$modes['random'] = chr(0x2f);

		$modes['jumpOut'] = chr(0x30);
		$modes['moveLeft'] = chr(0x31);
		$modes['moveRight'] = chr(0x32);
		$modes['scrollLeft'] = chr(0x33);
		$modes['scrollRight'] = chr(0x34);
		$modes['moveUp'] = chr(0x35);
		$modes['moveDown'] = chr(0x36);
		$modes['scrollToLR'] = chr(0x37);
		$modes['scrollUp'] = chr(0x38);
		$modes['scrollDown'] = chr(0x39);
		
		$modes['foldFromLR'] = chr(0x3a);
		$modes['foldFromUD'] = chr(0x3b);
		$modes['scrollToUD'] = chr(0x3c);
		$modes['shuttleFromLR'] = chr(0x3d);
		$modes['shuttleFromUD'] = chr(0x3e);
		$modes['peelOffL'] = chr(0x3f);
		
		$modes['peelOffR'] = chr(0x40);
		$modes['shutterFromUD'] = chr(0x41);
		$modes['shutterFromLR'] = chr(0x42);
		$modes['raindrops'] = chr(0x43);		
		$modes['randomMosaic'] = chr(0x44);		
		$modes['twinklingStars'] = chr(0x45);		
		$modes['hipHop'] = chr(0x46);
		$modes['radarScan'] = chr(0x47);
		$modes['fanOut'] = chr(0x48);
		$modes['fanIn'] = chr(0x49);
				
		$modes['spiralR'] = chr(0x4a);
		$modes['spiralL'] = chr(0x4b);
		$modes['toFourCorners'] = chr(0x4c);
		$modes['fromFourCorners'] = chr(0x4d);
		$modes['toFourSides'] = chr(0x4e);		
		$modes['fromFourSides'] = chr(0x4f);		
	
		$modes['scrollOutFromFourBlocks'] = chr(0x50);		
		$modes['scrollInToFourBlocks'] = chr(0x51);		
		$modes['moveOutFromFourBlocks'] = chr(0x52);		
		$modes['moveInToFourBlocks'] = chr(0x53);		
		$modes['scrollFromUpperLeftSquare'] = chr(0x54);		
		$modes['scrollFromUpperRightSquare'] = chr(0x55);		
		$modes['scrollFromLowerLeftSquare'] = chr(0x56);	
		$modes['scrollFromLowerRightSquare'] = chr(0x57);		
		$modes['scrollFromUpperLeftSlanting'] = chr(0x58);		
		$modes['scrollFromUpperRightSlanting'] = chr(0x59);	
			
		$modes['scrollFromLowerLeftSlanting'] = chr(0x5a);		
		$modes['scrollFromLowerRightSlanting'] = chr(0x5b);		

		$modes['moveInFromUpperLeftCorner'] = chr(0x5c);		
		$modes['moveInFromUpperRightCorner'] = chr(0x5d);		
		$modes['moveInFromLowerRightCorner'] = chr(0x5e);		
		$modes['moveInFromLowerRightCorner'] = chr(0x5f);		

		$modes['growingUp'] = chr(0x60);
		
		return $modes;	
	}

	function getDisplayModeBytes($simpleMoveLeft) {
		if ($simpleMoveLeft) {
			return chr(0x1b) . '0a';				
		}
		return chr(0x1b) . '0b';					
	}
	
	
	/*
	 * Constructs a message of the type that is used
	 * for uploading files to the sign. 
	 *
	 * It has a header with checksums, length bytes and 
	 * some additional bytes.
	 *
	 * This is currently used for uploading the SEQUENT.SYS file,
	 * but it could also be used for example for uploading a 
	 * new CONFIG.SYS.
	 *
	 * $length is the length of the actual file and $data contains
	 * the "command" for uploading the file. 
	 *
	 * This has been adapted from a Python implementation by 
	 * Michael Barton: http://www.weirdlooking.com/blog/108
	 */
	function constructMessage($data, $length) {
		
		$command[0] = 2;
		$command[1] = 2;
		$command[2] = 6;
		
		$isResponse = 0;
				
		$msg[0] = $length % 256;
		$msg[1] = (int)($length / 256);
		$msg[2] = 0;
		$msg[3] = 0;
		$msg[4] = 1; // group address. kokeilisko 1
		$msg[5] = 1; // unit address. kokeilisko 1
		$msg[6] = $this->sequence % 256;
		$msg[7] = (int)($this->sequence / 256);
		$msg[8] = $command[0];
		$msg[9] = $command[1];
		$msg[10] = $command[2];
		$msg[11] = $isResponse;
			
		for ($i = 12; $i < 12 + strlen($data); $i++) {
			$msg[$i] = ord($data[$i - 12]);	
		}
		
		$sum = array_sum($msg);
		$checksum1 = $sum % 256;
		$checksum2 = (int)($sum / 256);
		
		$this->sequence++;
		
		for ($i = 0; $i < count($msg); $i++) {
			$chrMsg = $chrMsg . chr($msg[$i]);	
		}
		
		$message = 'U' . chr(0xa7) . chr($checksum1) . chr($checksum2) . $chrMsg;

		return $message;
	}
	
	
	/*
	 * Returns the command for uploading a playlist file
	 * (SEQUENT.SYS) that points to one item on the RAM partition.
	 *
	 * The playlist will point to the file E:\T\AB. Calls to the
	 * function setText($text) modify that file.
	 */
	public function getSequentSysCommand() {
		
		$data = file_get_contents('SEQUENT.SYS', true);	

		// The upload command starts with some metadata bytes.
		// After them comes to actual content of the file to
		// be uploaded
		$commandPrefix = 
		
		"SEQUENT.SYS" .
		chr(0x00) .
		chr(strlen($data) % 256) . // number of bytes in the file % 256
		chr((int)(strlen($data) / 256)) . // number of bytes in the file / 256
		chr(0x00) .
		chr(0x00) .

		chr(0x00) .
		chr(0x03) .
		chr(0x01) .
		chr(0x00) .
		
		chr(0x01) .						
		chr(0x00) .						
		chr(0x00) .						
		chr(0x00);
				
		$fullCommand = $commandPrefix . $data;
		return $this->constructMessage($fullCommand, strlen($data));
	}		
}
