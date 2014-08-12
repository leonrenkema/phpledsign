<?php

/**
 * Description of Tcp
 *
 * @author leonrenkema
 */

namespace LedSign\Transport;

class Tcp {

    private $ip = '192.168.178.22';
    private $port = 9520;

    /**
     * 
     * @param type $cmd
     * @return type
     * @throws Exception
     */
    function sendCommand($cmd) {

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
