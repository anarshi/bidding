<?php
class Gwiusa_Bidding_Model_Bidding extends Mage_Core_Model_Abstract {
    public function __construct() {
        $this->_init('gwibidding/bidding');
        parent::_construct();
    }

    public function updateData( Mage_Customer_Model_Customer $customer, Mage_Catalog_Model_Product $product, $data) {
        try{
            if(!empty($data)) {
                $this->setCustomerId($customer->getId());
                $this->setEmail($customer->getEmail());
                $this->setProductId($product->getId());
                $this->setProductName($product->getName());
                $this->setSku($product->getSku());
                $this->setData('unit_price', $data['price']);
                $this->setData('qty', intval($data['qty']));
                $this->setData('expect_date', $data['expectdate']);
                $this->setCompany($customer->getData('company_name'));

                if(isset($data['warehouse_id'])) {
                    $this->setWarehouseId($data['warehouse_id']);
                }

                $this->setData('created_at', date('Y-m-d H:i:s'));

                if(isset($data['self_pick']) && $data['self_pick'] =='on'){
                    $this->setData('self_pick', 1);
                }
                else{
                    $this->setData('self_pick', 0);
                }
            }
            else {
                throw new Exception("Error Processing Request:  Insufficient Data Provided");
            }
        }
        catch(Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

}
