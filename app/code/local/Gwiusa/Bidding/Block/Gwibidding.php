<?php
class Gwiusa_Bidding_Block_Gwibidding extends Mage_Core_Block_Template {

    public function _prepareLayout() {
        return parent::_prepareLayout();
   }

   public function getCollection() {

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerId = $customer->getId();
        $collection = Mage::getModel('gwibidding/bidding')->getCollection()->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at','DESC');
        return $collection;
   }

   public function getBidding() {

        $bidding_id = Mage::registry('counteroffer');
        $bidding = Mage::getModel('gwibidding/bidding')->load((int)$bidding_id);
        return $bidding;
   }

}
