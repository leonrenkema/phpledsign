<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JetFile2
 *
 * @author leonrenkema
 */

namespace LedSign\Protocol;

class JetFile2 {

    const SOH = "\x01";
    const REV = 'Z';
    const ADDR = '00';
    const STX = "\x02";
    const EOT_ECHO = "\x03";
    const EOT_IN_ECHO = "\x04";
    
    /**
     * Sets the text displayed on the Led Sign to
     * $text. Accepts lot of special markup for changing
     * colors, using transitions, multiple frames and so on.
     */
    public function setText($text, $simpleMoveLeft = false) {
        $cmd = $this->getTextCommand($text, $simpleMoveLeft);
        return $this->sendCommand($cmd);
    }

    /**
     * Initializes the Led Sign for use with the PHP library.
     */
    public function initialize() {
        $cmd = $this->getSequentSysCommand();
        $success = $this->sendCommand($cmd);
        $this->setText('init ok');
    }

    /**
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
            '{blink}' => "\x071",
            '{/blink}' => "\x070",
            '{%m/%d/%C}' => chr(0x0b) . chr(0x20),
            '{%d/%m/%C}' => chr(0x0b) . chr(0x21),
            '{%m-%d-%C}' => chr(0x0b) . chr(0x22),
            '{%d-%m-%C}' => chr(0x0b) . chr(0x23),
            '{%m.%d.%C}' => chr(0x0b) . chr(0x24),
            '{%C}' => "\x0b\x25",       //chr(0x0b) . chr(0x25), // 
            '{%Y}' => "\x0b\x26", 
            '{%m}' => "\x0b\x27",  // month as number
            '{%b}' => "\x0b\x28",  // abbreviated month name
            '{%d}' => "\x0b\x29",  // day 01-31
            '{%u}' => "\x0b\x2a",  // weekday as decimal
            '{%a}' => "\x0b\x2b",  // abbreviated weekday 
            '{%H}' => "\x0b\x2c",
            '{%M}' => "\x0b\x2d", 
            '{%S}' => "\x0b\x2e", 
            '{%R}' => "\x0b\x2f",  // time on 24 hour notation
            '{%r}' => "\x0b\x30",  // time in am/pm notation
            '{celsius}' => "\x0b\x31", // temperature in celsius scale
            '{humidity}' => "\x0b\x32",  // humidity
            '{fahrenheit}' => "\x0b\x33",  // temperature in fahrenheit scale
            '{nf}' => chr(0x0c), // new frame
            '{nl}' => chr(0x0c), // new line
            '{left}' => chr(0x1e) . '1', // align left
            '{center}' => chr(0x1e) . '0', // center align
            '{right}' => chr(0x1e) . '2', // align right
            '{halfspace}' => chr(0x82), // half space
            
            '{red}' => "\x1c1", //chr(0x1c) . '1', // red
            '{green}' => "\x1c2", //chr(0x1c) . '2', // green
            '{amber}' => "\x1c3", //chr(0x1c) . '3', // amber/orange
            '{mixed1}' => chr(0x1c) . '4', // amber-green-red pysty
            '{mixed2}' => chr(0x1c) . '5', // yellow-green-red
            '{mixed3}' => chr(0x1c) . '6', // 
            '{mixed4}' => chr(0x1c) . '7', // 
            
            '{bgblack}' => chr(0x1d) . '0',
            '{bgred}' => chr(0x1d) . '1',
            '{bggreen}' => chr(0x1d) . '2',
            '{bgamber}' => chr(0x1d) . '3'
            
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
        foreach ($modes as $key => $hexValue) {
            $chars = chr(0x0a) . 'I' . $hexValue;
            $text = str_replace('{' . $key . 'In}', $chars, $text);
        }
        foreach ($modes as $key => $hexValue) {
            $chars = chr(0x0a) . 'O' . $hexValue;
            $text = str_replace('{' . $key . 'Out}', $chars, $text);
        }

        preg_match_all('/{s(\d)}/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $text = str_replace($match[0], chr(0x0f) . $match[1], $text);
        }

        return $text;
    }

    /**
     * Returns the command for setting the text displayed
     * on the sign to $text
     * 
     * @param String Text to display on the screen
     * 
     * @return String Command string
     */
    function getTextCommand($text, $simpleMoveLeft = false) {

        $cmd = self::SOH . self::REV . self::ADDR . self::STX . "A";
        //$savePath = "A";
        $cmd .= chr(0x0f) . "ETAB";
        $cmd .= chr(0x06);
        $cmd .= self::getDisplayModeBytes($simpleMoveLeft);
        //$colorSetting = self::getColorBytes('amber');
        $cmd .= chr(0x1a) . "1"; // 7x6, default
        $text = "{jumpOutOut}{jumpOutIn}" . $text;
        $cmd .= self::parseText($text);
        $cmd .= self::EOT_IN_ECHO;

        return $cmd;
    }
    
    function getSetTimeCommand() { 
        
        $cmd = chr(0x01) . "Z00" . chr(0x02);
        
        $cmd .= "EB";
        $cmd .= chr(date('y')) . chr(0x00) . chr(date('n')) . chr(date('j')) . chr(date('G')) . chr(date('i')). chr(date('N')) . chr(0x01);
        $cmd .= self::EOT_IN_ECHO;
        return $cmd;
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
        $msg[1] = (int) ($length / 256);
        $msg[2] = 0;
        $msg[3] = 0;
        $msg[4] = 1; // group address. kokeilisko 1
        $msg[5] = 1; // unit address. kokeilisko 1
        $msg[6] = $this->sequence % 256;
        $msg[7] = (int) ($this->sequence / 256);
        $msg[8] = $command[0];
        $msg[9] = $command[1];
        $msg[10] = $command[2];
        $msg[11] = $isResponse;

        for ($i = 12; $i < 12 + strlen($data); $i++) {
            $msg[$i] = ord($data[$i - 12]);
        }

        $sum = array_sum($msg);
        $checksum1 = $sum % 256;
        $checksum2 = (int) ($sum / 256);

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
        $commandPrefix = "SEQUENT.SYS" .
                chr(0x00) .
                chr(strlen($data) % 256) . // number of bytes in the file % 256
                chr((int) (strlen($data) / 256)) . // number of bytes in the file / 256
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
