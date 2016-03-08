<?php
class Gwiusa_Bidding_Model_Mysql4_Bidding extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {
        $this->_init('gwibidding/bidding','bidding_id');
    }
}
