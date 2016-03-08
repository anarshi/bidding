<?php
class Gwiusa_Bidding_Model_Status extends Varien_Object {
    const PENDING = 1;
    const APPROVED = 2;
    const DENY = 3;


    static public function toOptionArray() {
        return array(
            self::PENDING => Mage::helper('gwibidding')->__('pending'),
            self::APPROVED => Mage::helper('gwibidding')->__('approved'),
            self::DENY=>Mage::helper('gwibidding')->__('deny'),
        );
    }
}
