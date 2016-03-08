<?php
class Gwiusa_Bidding_Helper_Data extends Mage_Core_Helper_Abstract {
    public function encrypt_decrypt($string) {
        $string_length = strlen($string);
        $encrypted_string = "";

        for($position = 0; $position < $string_length; $position++) {
            $key = (($string_length+$position) + 1);
            $key = (255 + $key) % 255;
            $get_char_to_be_encrypted = substr($string, $position, 1);
            $ascii_char = ord($get_char_to_be_encrypted);
            $xored_char = $ascii_char ^ $key;
            $encrypted_char = chr($xored_char);
            $encrypted_string .= $encrypted_char;
        }

        return $encrypted_string;
    }
}
