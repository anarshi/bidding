<?php
class Gwiusa_Bidding_Block_Adminhtml_Bidding extends Mage_Adminhtml_Block_Widget_Grid_Container { 
    public function __construct() {
        $this->_controller = 'adminhtml_bidding';
        $this->_blockGroup = 'gwibidding';
        $this->_headerText = Mage::helper("gwibidding")->__("Customer bidding list");
        parent::__construct();
    }
}
