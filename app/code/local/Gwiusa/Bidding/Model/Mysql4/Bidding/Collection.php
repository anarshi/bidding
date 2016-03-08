<?php
class Gwiusa_Bidding_Model_Mysql4_Bidding_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
    public function _construct() {
        $this->_init('gwibidding/bidding');
        parent::_construct();
    }
}
