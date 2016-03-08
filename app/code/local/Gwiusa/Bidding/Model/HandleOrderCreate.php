<?php
class Gwiusa_Bidding_Model_HandleOrderCreate extends Mage_Core_Model_Abstract 
{
    private $_storeId = '1';
    private $_groupId = '1';
    private $_sendConfirmation = '0';
    private $orderData = array();
    private $_product;

    private $_sourceCustomer;
    private $_sourceOrder;

    public function setOrderInfo(Varien_Object $sourceOrder, Mage_Customer_Model_Customer $sourceCustomer)
    {
        $this->_sourceOrder = $sourceOrder;
        $this->_sourceCustomer = $sourceCustomer;

        $this->_product = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter('sku', 'some value here')
            ->addAttributeToSelect('*')
            ->getFirstItem();

        //Load full product data to product object
        $this->_product->load($this->_product->getId());

        $this->orderData = array (
            'session'=>array(
                'customer_id'=>$this->_sourceCustomer->getId(),
                'store_id'=>$this->_storeId,
            ),
            'payment'=>array(
                'method'=>'checkmo',
            ),
            'add_products'=>array(
                $this->_product->getId() => array('qty' => 1),
            ),
            'order' => array(
                'currency'=>'USD',
                'account'=>array(
                    'group_id'=>$this->_groupId,
                    'email'=>$this->_sourceCustomer->getEmail()
                ),
                'billing_address'=>array(
                    'customer_address_id'=>$this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'=>'',
                    'firstname'=>$this->_sourceCustomer->getFirstname(),
                    'middlename'=>'',
                    'lastname'=>$this->_sourceCustomer->getLastname(),
                    'suffix'=>'',
                    'company'=>$this->_sourceCustomer->getCompanyName(),
                    'street'=>array($this->_sourceCustomer->getStreet(), ''),
                    'city'=>$this->_sourceCustomer->getCity(),
                    'country_id'=>$this->_sourceCustomer->getCountryId(),
                    'region'=>'',
                    'region_id'=>$this->_sourceCustomer->getRegionId(),
                    'postcode'=>$this->_sourceCustomer->getPostcode(),
                    'telephone'=>$this->_sourceCustomer->getTelephone(),
                    'fax'=>'',
                ),
                'shipping_address'=>array(
                    'customer_address_id'=>$this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'=>'',
                    'firstname'=>$this->_sourceCustomer->getFirstname(),
                    'middlename'=>'',
                    'lastname'=>$this->_sourceCustomer->getLastname(),
                    'suffix'=>'',
                    'company'=>$this->_sourceCustomer->getCompanyName(),
                    'street'=>array($this->_sourceCustomer->getStreet(), ''),
                    'city'=>$this->_sourceCustomer->getCity(),
                    'country_id'=>$this->_sourceCustomer->getCountryId(),
                    'region'=>'',
                    'region_id'=>$this->_sourceCustomer->getRegionId(),
                    'postcode'=>$this->_sourceCustomer->getPostcode(),
                    'telephone'=>$this->_sourceCustomer->getTelephone(),
                    'fax'=>'',
                ),
                'shipping_method'=>'',
                'comment'=>array(
                    'customer_note'=>'This order has been placed by auction',
                ),
                'send_confirmation'=>$this->_sendConfirmation
            ),
        );
    }

    protected function _getOrderCreateModel() {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    protected function _getSession() {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    protected function _iniSession($data) {
        if(!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int) $data['customer_id']);
        }

        if(!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int) $data['store_id']);
        }

        return $this;
    }

    public function create() {
        $orderData = $this->orderData;
        
        if(!empty($orderData)) {
            $this->_initSession($orderData['session']);

            try{
                $this->_processQuote($orderData);
                if(!empty($orderData['payment'])) {
                    $this->_getOrderCreateModel()->setPaymentData($orderData['payment']);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($orderData['payment']);
                }

                $item = $this->_getOrderCreateModel()->getQuote()->getItemByProduct($this->_product);

                $item->addOption(new Varien_Object(
                        array(
                            'product'=>$this->_product, 
                            'code'=>'option_ids', 
                            'value'='5' /* Option id goes here. If more options, then comma separate */
                        )
                    )
                );

                $item->addOption(new Varien_Object(
                    array(
                        'product'=>$this->_product,
                        'code'=>'option_5',
                        'value'=>'some value here',
                    )
                  )
                );

                Mage::app()->getStore()->getConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, '0');

                $_order = $this->_getOrderCreateModel()->importPostData($orderData['order'])->createOrder();

                $this->_getSession()->clear();
                Mage::unregister('rule_data');

                return $_order;
            }
            catch (Exception $e) {
                Mage::log("order save error...");
            }
        }

        return null;
    }

    protected function _processQuote($data = array()) {

        //saving order data
        if(!empty($data['order'])) {
            $this->_getOrderCreateModel()->importPostData($data['order']);
        }

        $this->_getOrderCreateModel()->getBillingAddress();
        $this->_getOrderCreateModel()->setShippingAsBilling(true);

        // just like adding product from Magento admin grid
        if(!empty($data['add_products'])) {
            $this->_getOrderCreateModel()->addProducts($data['add_products']);
        }

        //collect shipping rates
        $this->_getOrderCreateModel()->collectShippingRates();

        //Add payment data
        if(!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payament']);
        }

        $this->_getOrderCreateModel()->initRuleData()->saveQuote();

        if(!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayament()->addData($data['payment']);
        }

        return $this;

    }
}
