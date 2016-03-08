<?php
class Gwiusa_Bidding_IndexController extends Mage_Core_Controller_Front_Action {
    public function preDispatch() {
        parent::preDispatch();
        if(!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->getResponse()->setRedirect(Mage::helper('customer')->getLoginUrl());
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    public function detailsAction() {
        $helper = Mage::helper('gwibidding');

        if(!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());
            $this->_redirect('customer/account/login');
        }
        
        $current =  Mage::getSingleton('customer/session')->getCustomer()->getId();

        $id = $this->getRequest()->getParam('id', null);

        $bidding = Mage::getModel('gwibidding/bidding');
        
        if($id) {
            $bidding->load((int) $id);
            $customer_id = $bidding->getData('customer_id');
            $biddingdata = $bidding->getData(); 
            $flag  = $bidding->getData('accepted');
	    $flag1 = $bidding->getData('status');

            if (strlen($flag) > 0) {
                $this->_redirect('/');
            }
/*
            if ($flag1 != 'pending' ) {
                $this->_redirect('/');
            }
*/
            if( ((int)$customer_id != (int)$current) || empty($biddingdata) ) {
                $this->_redirect('/');
            }
        }
        else{
            Mage::getSingleton('core/session')->addError(Mage::helper('gwibidding')->__('The Bidding does not exit'));
        }

        Mage::register('counteroffer', $id);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function processAction() {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $name = $customer->getName();

        try{
            $data = $this->getRequest()->getParams();

            $accept = '';

            if(isset($data['accept'])) {
                $accept = 'accepted';
            }

            if(isset($data['decline'])) {
                $accept = 'declined';
            }

            $id = $data['bidding_id'];
            $bidding = Mage::getModel('gwibidding/bidding')->load($id);
            $productId = $bidding->getData('product_id');
            $_product = Mage::getModel('catalog/product')->load($productId);
            $uom =  $_product->getResource()->getAttribute('uom')->getFrontend()->getValue($_product);
            $customer_id = $bidding->getData('customer_id');

            $customer = Mage::getModel('customer/customer')->load($customer_id);
            $billing_address_id = $customer->getDefaultBilling();

            if((int)$billing_address_id){
                $address = Mage::getModel('customer/address')->load($billing_address_id);
                $billing_city = $address->getData('city');
                $billing_street = $address->getData('street');
                $billing_state = $address->getData('region');
                $billing_postcode = $address->getData('postcode');
                $billing_country = $address->getData('country_id');
            }

            $shipping_address_id = $customer->getDefaultShipping();
            if((int)$shipping_address_id){
                $shipping_address = Mage::getModel('customer/address')->load($shipping_address_id);
                $shipping_city = $address->getData('city');
                $shipping_street = $address->getData('street');
                $shipping_state = $address->getData('region');
                $shipping_postcode = $address->getData('postcode');
                $shipping_country = $address->getData('country_id');
            }

            $company_name = $customer->getData('company_name');

            $bidding->setData('accepted', $accept);
            $bidding->save();
            
            $unit_price_offerred = $bidding->getData('unit_price_offerred') ? (double) $bidding->getData('unit_price_offerred') : 0;
            $unit_price = $bidding->getData('unit_price');

            $product_name = $bidding->getData('product_name');
            $qty = $bidding->getData('qty');
            $expectdate = $bidding->getData('expect_date');
            $final_price = ($unit_price >= $unit_price_offerred) ? $unit_price : $unit_price_offerred; 


            if(isset($data['accept'])) {
                $successMessage= Mage::helper('gwibidding')->__('You have accepted the offer. You will receive an order confirmation email shortly. We will prepare your order by the Expected shipping date.');
                Mage::getSingleton('core/session')->addSuccess($successMessage);
            }

            if(isset($data['decline'])) {
                $successMessage= Mage::helper('gwibidding')->__('You have declined the offer from a bidder. Your request is closed without a matched price.');
                Mage::getSingleton('core/session')->addSuccess($successMessage);
            }

            $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
            $emailTemplate = Mage::getModel('core/email_template')->loadDefault('notificationadmin');

            $bid_id = 9000000 + $id;
            $emailVariables = array();
            $emailVariables['bidding_id'] = $bid_id;
            $emailVariables['name'] = $name;
            $emailVariables['product_name'] = $product_name;
            $emailVariables['qty'] =  $qty;
            $emailVariables['unit_price'] = $final_price;
            $emailVariables['expect_date'] = $expectdate;
            $emailVariables['storename'] = 'ingredientsonline.com';
            $emailVariables['accept'] = $accept;
            $emailVariables['uom'] = $uom; 
            $emailVariables['company_name'] = $company_name;
            $emailVariables['billing_street_line1'] = $billing_street;
            $emailVariables['billing_city'] = $billing_city;
            $emailVariables['billing_zipcode'] = $billing_postcode;
            $emailVariables['billing_state'] = $billing_state;
            $emailVariables['billing_country'] =  $billing_country;
            $emailVariables['shipping_street_line1'] = $shipping_street;
            $emailVariables['shipping_city'] = $shipping_city;
            $emailVariables['shipping_zipcode'] = $shipping_postcode;
            $emailVariables['shipping_state'] = $shipping_state;
            $emailVariables['shipping_country'] =  $shipping_country;
                
            $processedTemplate = $emailTemplate->getProcessedTemplate($emailVariables);

            $mail = Mage::getModel('core/email')
                ->setToName('Customer Service')
                ->setToEmail(array('llo@ingredientsonline.com', 'txue@ingredientsonline.com', 'jwang@gulinbio.com', 'wyiu@ingrediensonline.com'))
                ->setBody($processedTemplate)
                ->setSubject('Bid Request #'. $bid_id . ' -offer: ' .$accept)
                ->setFromEmail($senderEmail)
                ->setFromName('ingredientsonline.com')
                ->setType('html');

            try {
                $mail->send();
            }
            catch(Exception $e) {
                //notification
            }
        }
        catch(Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('*/*/');
        }

        $this->_redirect('gwibidding/index/thankyou');

    }

    public function editAction() {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    public function historyAction() {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    public function thankyouAction() {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    public function newPostAction() {
        try{
            $data = $this->getRequest()->getParams();

            $bidding = Mage::getModel('gwibidding/bidding');
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $name = $customer->getName();
            $productId = intval($this->getRequest()->getParam('productId'));
            $product = Mage::getModel('catalog/product')->load($productId);
            $product_name = $product->getName();
            $email = $customer->getData('email');

            $uom =  $product->getResource()->getAttribute('uom')->getFrontend()->getValue($product);

            if($this->getRequest()->getPost() && !empty($data)) {
                $bidding->updateData($customer, $product, $data);
                $bidding->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $bidding->setData('status', 'pending');

                if( isset($data['self_pick']) && !empty($data['self_pick']) ) {
                    $bidding->setData('self_pick', 1);
                }

                $bidding->save();

                $id = $bidding->getData('bidding_id');

                $bidding_id = 9000000 + $id;

                $successMessage= Mage::helper('gwibidding')->__('Your request has been submitted successfully. We will process your request within 1 business day.');

                Mage::getSingleton('core/session')->addSuccess($successMessage);

                $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');

                $emailTemplate = Mage::getModel('core/email_template')->loadDefault('notifybid');

                $emailVariables = array();
                $emailVariables['bidding_id'] = $bidding_id;
                $emailVariables['name'] = $name;
                $emailVariables['product_name'] = $product_name;
                $emailVariables['qty'] = $data['qty'];
                $emailVariables['unit_price'] = $data['price'];
                $emailVariables['expect_date'] = $data['expectdate'];
                $emailVariables['storename'] = 'ingredientsonline.com';
                $emailVariables['uom'] = $uom;
                if(isset($data['self_pick'])) {
                    $emailVariables['self_pick'] = $data['self_pick'];
                }

                $processedTemplate = $emailTemplate->getProcessedTemplate($emailVariables);
                
                $mail = Mage::getModel('core/email')
                    ->setToName($name)
                    ->setToEmail(array($email, 'llo@ingredientsonline.com'))
                    ->setBody($processedTemplate)
                    ->setSubject('Bid Request #'. $bidding_id . ' confirmation')
                    ->setFromEmail($senderEmail)
                    ->setFromName('ingredientsonline.com')
                    ->setType('html');

                $mail1 = Mage::getModel('core/email')
                    ->setToName($name)
                    ->setToEmail(array('wyiu@ingredientsonline.com', 'llo@ingredientsonline.com', 'jwang@gulinbio.com'))
                    ->setBody($processedTemplate)
                    ->setSubject('Bid Request #'. $bidding_id . ' confirmation')
                    ->setFromEmail($senderEmail)
                    ->setFromName('ingredientsonline.com')
                    ->setType('html');

                try {
                    $mail->send();
		    $mail1->send();
                }
                catch(Exception $e) {
                }
            }
            else {
                throw new Exception("Insufficient Data Provided");
            }
        }
        catch(Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('*/*/');
        }
        
        $this->_redirect('gwibidding/index/success');
    }

    public function successAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

/*
    public function updateData( Mage_Customer_Model_Customer $customer, Mage_Catalog_Model_Product $product, $data) {
        try{
            if(!empty($data)) {
                $this->setCustomerId($customer->getId());
                $this->setEmail($customer->getEmail());
                $this->setProductId($product->getId());
                $this->setProductName($product->getName());
                $this->setSku($product->getSku());
                $this->setUnitPrice($data['unit_price']);
                $this->setQty($data['qty']);
                $this->setExpectDate($data['expect_date']);
                $this->setWarehouseId($data['warehouse_id']);
                $this->setCreatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                $this->setSelfPick($data['self_pick']);
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
*/
    public function indexAction () {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) 
        {
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());
            $this->_redirect('customer/account/login');
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function testAction() {
        $response = "test";
        $this->loadLayout(array('default', 'gwiusa/bidding/test.phtml'));
        $block = $this->getLayout()->createBlock('gwibidding/test')->setTemplate('gwiusa/bidding/test.phtml')->assign('data', $response);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

}
