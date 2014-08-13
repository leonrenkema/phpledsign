<?php

namespace LedSign\Transport;

/**
 * TCP transport
 *
 * @author leonrenkema
 */
class Tcp {

    private $ip = '192.168.178.22';
    private $port = 9520;

    function __construct($ip = null, $port = null) {
        if ($ip !== null) { 
            $this->ip = $ip;
        }
        
        if ($port !== null) {
            $this->port = $port;
        }
    }
    
    /**
     * Send a command to the device
     * 
     * @param type $cmd
     * @return type
     * @throws Exception
     */
    public function sendCommand($cmd) {

        $fp = fsockopen("tcp://{$this->ip}", $this->port, $errno, $errstr);
        stream_set_timeout($fp, 5);
        
        if (!$fp) {
            echo "ERROR: $errno - $errstr<br />\n";
            // @todo fix
            throw new Exception(); 
        } else {
            fwrite($fp, $cmd);
            fclose($fp);
        }
        
        return;
    }

}
