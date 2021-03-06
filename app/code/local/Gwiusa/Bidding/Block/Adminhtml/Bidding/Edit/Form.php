<?php
class Gwiusa_Bidding_Block_Adminhtml_Bidding_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected function _prepareForm() {

        $form = new Varien_Data_Form(array(
            'id'=>'edit_form',
            'action'=>$this->geturl('*/*/save', array('id'=>$this->getRequest()->getParam('id'))),
            'method'=>'post',
            'enctype'=>'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        if(Mage::getSingleton('adminhtml/session')->getFormData()) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData();
            Mage::getSingleton('adminhtml/session')->setFormData(null);
        }
        elseif(Mage::registry('registry_data')) {
            $data = Mage::registry('registry_data')->getData();
        }
        else{
            $data = new Varien_Object(); 
        }

        $productId = $data['product_id'];
        $_product = Mage::getModel('catalog/product')->load($productId);
        $childProducts = Mage::getSingleton('catalog/product_type_configurable')->getUsedProducts(null, $_product);

        $tierprice = $_product->getTierPrice($data['qty']);
        $baseprice = $_product->getFinalPrice();

        $lowestPrice = $tierprice > 0 ? $tierprice : $baseprice;

        $uom =  $_product->getResource()->getAttribute('uom')->getFrontend()->getValue($_product);

        $url = 'http://docs.ingredientsonline.com/lsws/api_bid_price.php?';
        $url .= 'sku='.$data['sku'].'&qty='.$data['qty'].'&unit='.$uom;

        $json = file_get_contents($url);

        $obj = json_decode($json);

        //$suggest_price = 0;

        if(!is_null($obj) && $obj->unit_price)
            $suggest_price = Mage::helper('core')->currency($obj->unit_price, true, false);
        else 
            $suggest_price = Mage::helper('core')->currency($lowestPrice, true, false);

        $val = 1;
        if(isset($data['status'])) {
            $str = $data['status'];
            switch($str) {
                case 'pending':
                   $val = 1;
                   break;
                case 'approved':
                   $val = 2;
                   break;
                case 'deny':
                   $val = 3;
                   break;
            }
        }

        $data['status'] = $val;

        $fieldset = $form->addFieldset('registry_form', array('legend'=>Mage::helper('gwibidding')->__('Customer biding information')));

        $fieldset->addField('customer_id', 'label', array('label'=>Mage::helper('gwibidding')->__('Customer Id'), 'name'=>'customer_id',));

        $fieldset->addField('company', 'label', array('label'=>Mage::helper('gwibidding')->__('Company Name'), 'name'=>'company',));

        $fieldset->addField('product_id', 'label', array('label'=>Mage::helper('gwibidding')->__('Product Id'), 'name'=>'product_id'));

        $fieldset->addField('product_name', 'label', array('label'=>Mage::helper('gwibidding')->__('Product Name'), 'name'=>'product_name'));

        $fieldset->addField('email', 'label', array('label'=>Mage::helper('gwibidding')->__('Email'), 'name'=>'email'));

        $fieldset->addField('sku', 'label', array('label'=>Mage::helper('gwibidding')->__('SKU'), 'name'=>'sku'));

        $fieldset->addField('accepted', 'label', array('label'=>Mage::helper('gwibidding')->__('Counter Offer status'), 'name'=>''));

        $fieldset->addField('unit_price', 'text', array('label'=>Mage::helper('gwibidding')->__('Unit Price'), 'class'=>'required-entry', 'required'=>true, 'name'=>'unit_price'));

        $fieldset->addField('unit_price_offerred', 'text', array('label'=>'Counter Unit Price (lowest price: ' .$suggest_price. ')', 'name'=>'unit_price_offerred', 'width'=>'300px'));

        $fieldset->addField('qty', 'text', array('label'=>Mage::helper('gwibidding')->__('Quantity'), 'class'=>'required-entry', 'required'=>true, 'name'=>'qty'));

        //$fieldset->addField('qty_offerred', 'text', array('label'=>Mage::helper('gwibidding')->__('Counter Quantity'), 'name'=>'qty_offerred'));

        $fieldset->addField('expect_date', 'text', array('label'=>Mage::helper('gwibidding')->__('Expect Shipping Date'), 'class'=>'required-entry', 'required'=>true, 'name'=>'expect_date'));
        
        $fieldset->addField('status', 'select', array('label'=>Mage::helper('gwibidding')->__('status'),'values'=>Mage::getModel('gwibidding/status')->toOptionArray(),  'class'=>'required_entry', 'required'=>true, 'name'=>'status'));

        $fieldset->addField('self_pick', 'checkbox', array('label'=>Mage::helper('gwibidding')->__('Self Pickup'), 'checked'=>(int) $data['self_pick'] > 0 ? 'checked': '','onclick'=>'this.value = this.checked ? 1 : 0;', 'name'=>'self_pick'));

        $form->getElement('self_pick')->setSelfPick(!empty($data['self_pick']));
        $form->setValues($data);
        return parent::_prepareForm();
    }

    private function getMinPrice($Id) {
        $pricesByAttributeValue = array();
        $max = '';
        $min = '';
        $product = Mage::getModel('catalog/product')->load($Id);
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        $basePrice = $product->getFinalPrice();

        foreach($attributes as $attribute) {
            $prices= $attribute->getPrices();

            foreach($prices as $price) {
                //var_dump($price);
                //exit();
                if($price['is_percent']) {
                    $pricesByAttributeValue[$price['value_index']] = (float) $price['pricing_value'] * $basePrice / 100;
                }
                else{
                    $pricesByAttributeValue[$price['value_index']] = (float) $price['pricing_value'];
                }
            }
        }

        $simple = $product->getTypeInstance()->getUsedProducts();

        foreach($simple as $product) {
            $totalPrice = $basePrice;

            foreach($attributes as $attribute) {
                $value = $product->getData($attribute->getProductAttribute()->getAttributeCode());
                if(isset($pricesByAttributeValues[$value])) {
                    $totalPrice += $pricesByAttributeValues[$value];
                }
            }
            if(!$min || $totalPrice < $min) {
                $min = $totalPrice;
            }
        }
        
        return $min;
    }
}
