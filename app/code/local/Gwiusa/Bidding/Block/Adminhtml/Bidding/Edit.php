<?php
class Gwiusa_Bidding_Block_Adminhtml_Bidding_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
    public function __construct() {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'gwibidding';
        $this->_controller = 'adminhtml_bidding';
        $this->_mode = 'edit';
        
        $this->_updateButton('save', 'label', Mage::helper('gwibidding')->__('Save Bid'));
        $this->_updateButton('delete', 'label', Mage::helper('gwibidding')->__('Delete Bid'));
    }

    public function getHeaderText() {
        if(Mage::registry('registry_data') && Mage::registry('registry_data')->getId())
            return Mage::helper('gwibidding')->__("Edit bid '%s'", $this->htmlEscape(Mage::registry('registry_data')->getData('product_name')));
    }
}
