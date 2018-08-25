<?php
class Lotusbreath_Checkout_Service_Task_Shipping extends Lotusbreath_Checkout_Service_Task_Abstract{


    public function execute(){

        /*
        $stepData = $this->getSession()->getStepData('osc_shipping');

        if(isset($stepData['the_same']) && $stepData['the_same'] == true){
            return isset($stepData['errors']) ? $stepData['errors'] : false;
        }
        */
        $shippingAddressId = $this->getRequest()->getPost('shipping_address_id', null);
        $data = $this->getRequest()->getPost('billing', null);
        $isUseForShipping = !empty($data['use_for_shipping']) ? $data['use_for_shipping'] : null;
        if ($isUseForShipping){
            $shippingData = $data;
        }else{
            $shippingData = $this->getRequest()->getPost('shipping', null);
        }

        if(Mage::getStoreConfig("lotusbreath_onestepcheckout/shippingaddress/useshortshippingaddress")){
            $hiddenFields = array('firstname','lastname','company');
            foreach($hiddenFields as $_hiddenField){
                $shippingData[$_hiddenField] = $data[$_hiddenField];
            }

        }
        $errors =  $this->getQuoteAddressService()->saveShippingAddress($shippingData, $shippingAddressId);
        $success = true;
        if($errors){
            $success = false;
        }
        $taskLog = $this->getSession()->getData('shipping');
        if($taskLog == false)
            $taskLog = array();

        $taskLog = array_merge($taskLog, array(
                'success' => $success,
                'previous_data' => $shippingData,
                'errors' => $errors
            )
        );

        $this->getSession()->setData('shipping', $taskLog);
        return $errors;

        /*
        $stepData = $this->getSession()->getStepData('osc_shipping');
        if($stepData == false)
            $stepData = array();

        $stepData = array_merge($stepData, array(
                'success' => $success,
                'previous_data' => $shippingData,
                'errors' => $errors
            )
        );
        $this->getSession()->setStepData('osc_shipping', $stepData);
        return $errors;
        */
    }
}