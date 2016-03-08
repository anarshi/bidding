<?php
class Gwiusa_Bidding_Adminhtml_BiddingController extends Mage_Adminhtml_Controller_Action {
    public function indexAction() {
        //echo "This is the admin bidding controller";
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    public function editAction() {
        $id = $this->getRequest()->getParam('id', null);
        $bidding = Mage::getModel('gwibidding/bidding');

        if($id) {
            $bidding->load((int) $id);

            if($bidding->getId()) {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if($data) {
                    $bidding->setData($data)->setId($id);
                }
            }
            else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('awesome')->__('The bidding does not exist'));
            }
        }

        Mage::register('registry_data', $bidding);

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }

    public function deleteAction() {
            try{
                $id = $this->getRequest()->getParam('id');

                if($id) {
                    $bidding = Mage::getModel('gwibidding/bidding')->load($id);
                    $bidding->delete();
                    $this->_redirect('*/*/index');
                }
            }
            catch(Exception $e) {
                $this->_getSession()->addError(Mage::helper('gwibidding')->__('An error occurred while deleting the bidding data. Please review the log and try again.')); 
                Mage::logException($e);
                $this->_redirect('*/*/edit', array('id'=>$this->getRequest()->getParam('id')));

                return $this;
            }
    }

    public function saveAction() {
        $helper = Mage::helper('gwibidding');
	$session = Mage::getSingleton('admin/session');
        $adminUser = $session->getUser()->getUsername();
        if($this->getRequest()->getPost()) {
            try{
                $data = $this->getRequest()->getPost();

                $id = $this->getRequest()->getParam('id');

                if($data && $id) {
                    $bidding_id = 9000000 + $id;
                    $bidding = Mage::getModel('gwibidding/bidding')->load($id);
                    $qty = $bidding->getData('qty');
                    $expect_date = $bidding->getData('expect_date');
                    $customer_id = $bidding->getData('customer_id');
                    $product_id = $bidding->getData('product_id');
                    $original_status = $bidding->getData('status');
			$selfpickup = $bidding->getData('self_pick');
                    if($selfpickup == 1){
			 $selfpick = 'Shipping Method: Self Pickup';
		    }
                    else {
			$selfpick = ''; 
		    }
                    //customer information
                    $customer = Mage::getModel('customer/customer')->load($customer_id);
                    $company_name = $customer->getData('company_name');

                    $billing_address_id = $customer->getDefaultBilling();
                    if((int)$billing_address_id){
                        $address = Mage::getModel('customer/address')->load($billing_address_id);
                        $billing_city = $address->getData('city');
                        $streetArray1 = $address->getData('street');
                        $billing_street =$streetArray1;// join(' ', $streetArray1);
                        $billing_state = $address->getData('region');
                        $billing_postcode = $address->getData('postcode');
                        $billing_country = $address->getData('country_id');
                     }

                     $shipping_address_id = $customer->getDefaultShipping();

                     if((int)$shipping_address_id){
                        $shipping_address = Mage::getModel('customer/address')->load($shipping_address_id);
                        $shipping_city = $address->getData('city');
                        $streetArray2 = $address->getData('street');
                        $shipping_street = $streetArray2; //join(' ', $streetArray2);
                        $shipping_state = $address->getData('region');
                        $shipping_postcode = $address->getData('postcode');
                        $shipping_country = $address->getData('country_id');
                     }

                    //product information
                    $product = Mage::getModel('catalog/product')->load($product_id);
                    $uom =  $product->getResource()->getAttribute('uom')->getFrontend()->getValue($product);
                    $email = $customer->getEmail();
                    $name = $customer->getName();
                    $storename = 'ingredientsonline.com';
                    $product_name = $product->getName();
                    $unit_price = $bidding->getData('unit_price');
                    
                    $bidding->setData('unit_price', $data['unit_price']);
                    $bidding->setData('qty', $data['qty']);
                    $bidding->setData('expect_date', $data['expect_date']);
                    $statusVal = $data['status'];

                    $status = '';

                    switch((int)$statusVal) {
                        case 1:
                            $status = 'pending';
                            break;
                        case 2:
                            $status = 'approved';
                            break;
                        case 3:
                            $status = 'deny';
                            break;
                        default:
                            $status = 'pending';
                    }

                    $flag = false;

                    $counter_price_offerred = $data['unit_price_offerred'];

                    if(isset($data['unit_price_offerred']) && !empty($counter_price_offerred)) {
                         $newPrice = $data['unit_price_offerred'];
                         $oldPrice = $bidding->getData('unit_price_offerred');

                         if( ($newPrice != $oldPrice) || empty($oldPrice) ) 
                         {
                            $flag = true;
                         }
                         $counter_unit_price = $data['unit_price_offerred'];
                         $bidding->setData('unit_price_offerred', $data['unit_price_offerred']);
                    }


                    $bidding->setData('status', $status);

                    if(isset($data['self_pick']) && !empty($data['self_pick'])) {
                        $bidding->setData('self_pick', $data['self_pick']); 
                    }
                    else {
                        $bidding->setData('self_pick', 0);
                    }
		    $bidding->setData('last_modified_by', $adminUser);
                    $bidding->save();

                    if($flag) {
                        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
                        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('counterbid');
                        $emailVariables = array();
                        $emailVariables['name'] = $name;
                        $emailVariables['product_name'] = $product_name;
                        $emailVariables['qty'] = $qty;
                        $emailVariables['counter_unit_price'] = $counter_unit_price;
                        $emailVariables['expect_date'] = $expect_date ;
                        $emailVariables['unit_price'] = $unit_price;
                        $emailVariables['storename'] = 'ingredientsonline.com';
                        $emailVariables['uom'] = $uom;
                        $emailVariables['bidding_id'] = $bidding_id;
                        $emailVariables['url'] = Mage::getBaseUrl(). 'gwibidding/index/details/id/'. $id;
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
                        $emailVariables['company_name'] = $company_name;

                        $processedTemplate = $emailTemplate->getProcessedTemplate($emailVariables);

                        $mail = Mage::getModel('core/email')
                            ->setToName($name)
                            ->setToEmail($email)
                            ->setBody($processedTemplate)
                            ->setSubject('Bid Request #'. $bidding_id . ' -counter offer')
                            ->setFromEmail($senderEmail)
                            ->setFromName('ingredientsonline.com')
                            ->setType('html');
                        try{
                            $mail->send();
                        }
                        catch(Exception $e){
                            
                        }
                    }

                    if(($original_status != $status) && $statusVal == '2') {
                        $final_price = (float)$counter_price_offerred > (float)$unit_price ? (float)$counter_price_offerred : (float)$unit_price ;
                        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
                        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('approvebid');
                        $emailVariables = array();
                        $emailVariables['name'] = $name;
                        $emailVariables['product_name'] = $product_name;
                        $emailVariables['uom'] = $uom;
                        $emailVariables['storename'] = 'ingredientsonline.com';
                        $emailVariables['qty'] = $qty;
                        $emailVariables['expect_date'] = $expect_date ;
                        $emailVariables['bidding_id'] = $bidding_id;
                        $emailVariables['unit_price'] = $final_price;
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
                        $emailVariables['company_name'] = $company_name;
			$emailVariables['selfpick'] = $selfpick;

                        $processedTemplate = $emailTemplate->getProcessedTemplate($emailVariables);

                        $mail = Mage::getModel('core/email')
                            ->setToName($name)
                            ->setToEmail(array($email, 'service@ingredientsonline.com'))
                            ->setBody($processedTemplate)
                            ->setSubject('Bid Request #'.$bidding_id. ' -offer matched')
                            ->setFromEmail($senderEmail)
                            ->setFromName('ingredientsonline.com')
                            ->setType('html');

                        $mail1 = Mage::getModel('core/email')
                            ->setToName($name)
                            ->setToEmail('service@ingredientsonline.com')
                            ->setBody($processedTemplate)
                            ->setSubject('Bid Request #'.$bidding_id. ' -offer matched')
                            ->setFromEmail($senderEmail)
                            ->setFromName('ingredientsonline.com')
                            ->setType('html');
                        try{
                            $mail->send();
                            $mail1->send();
                        }
                        catch(Exception $e){
                            
                        }
                    }

                    if(($original_status != $status) && $statusVal == '3') {
                        $final_price = (float)$counter_price_offerred > (float)$unit_price ? (float)$counter_price_offerred : (float)$unit_price ;

                        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');
                        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('denybid');
                        $emailVariables = array();
                        $emailVariables['name'] = $name;
                        $emailVariables['product_name'] = $product_name;
                        $emailVariables['uom'] = $uom;
                        $emailVariables['storename'] = 'ingredientsonline.com';
                        $emailVariables['qty'] = $qty;
                        $emailVariables['expect_date'] = $expect_date ;
                        $emailVariables['bidding_id'] = $bidding_id;
                        $emailVariables['unit_price'] = $final_price;

                        $processedTemplate = $emailTemplate->getProcessedTemplate($emailVariables);

                        $mail = Mage::getModel('core/email')
                            ->setToName($name)
                            ->setToEmail($email)
                            ->setBody($processedTemplate)
                            ->setSubject('Bid Request #'. $bidding_id . ' -closed without a match')
                            ->setFromEmail($senderEmail)
                            ->setFromName('ingredientsonline.com')
                            ->setType('html');
                        try{
                            $mail->send();
                        }
                        catch(Exception $e){
                            
                        }
                    }


                    $this->_redirect('*/*/edit', array('id'=>$this->getRequest()->getParam('id')));
                }
            }
            catch(Exception $e) {
                $this->_getSession()->addError(Mage::helper('gwibidding')->__('An error occurred while saving the bidding data. Please review the log and try again.'));
                Mage::logException($e);
                $this->_redirect('*/*/edit', array('id'=>$this->getRequest()->getParam('id')));

                return $this;
            }
        }
    }

    public function massDeleteAction() {
        $registryIds = $this->getRequest()->getParam('gwibiddings');

        if(!is_array($registryIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('gwibidding')->__('Please select one or more bidding item'));
        }
        else {
            try{
                foreach($registryIds as $Id) {
                    $bid = Mage::getModel('gwibidding/bidding')->load($Id);
                    $bid->delete();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('gwibidding')->__('Total of %d records were deleted.', count($registryIds)));
            }
            catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction() {
        $fileName = 'gwibid.csv';
        $grid = $this->getLayout()->createBlock('gwibidding/adminhtml_bidding_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function exportExcelAction()
    {
        $fileName   = 'gwibid.xml';
        $grid       = $this->getLayout()->createBlock('gwibidding/adminhtml_bidding_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

}
