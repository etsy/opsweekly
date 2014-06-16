<?php 

function sendIRCMessage($message, $destination) {
    global $irccat_hostname, $irccat_port;

    if ($irccat_hostname !== '' && $irccat_port !== '') {

        $fh = fsockopen($irccat_hostname, $irccat_port);
        if ($fh) {
            fwrite($fh, "$destination $message");
            fclose($fh);
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
