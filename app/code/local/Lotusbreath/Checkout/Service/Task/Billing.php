<?php
class Lotusbreath_Checkout_Service_Task_Billing extends Lotusbreath_Checkout_Service_Task_Abstract{


    public function execute(){

        /*
        $stepData = $this->getSession()->getStepData('osc_billing');
        if(isset($stepData['the_same']) && $stepData['the_same'] == true){
            return isset($stepData['errors']) ? $stepData['errors'] : false;
        }
        */
        $billingAddressId = $this->getRequest()->getPost('billing_address_id', null);
        $data = $this->getRequest()->getPost('billing', array());

        $isUseSameToShipping = !empty($data['use_same_shipping']) ? $data['use_same_shipping'] : null;
        if ($isUseSameToShipping){
            $billingAddressId = $this->getRequest()->getPost('shipping_address_id', null);
            $data = $this->_cpAddress($this->getRequest()->getPost('shipping', null),$data);
        }

        $errors = $this->getQuoteAddressService()->saveBillingAddress($data, $billingAddressId);
        $success = true;
        if($errors){
            $success = false;
        }
        /*
        $stepData = $this->getSession()->getStepData('osc_billing');
        if($stepData == false)
            $stepData = array();
        $stepData = array_merge($stepData, array(
                'success' => $success,
                'previous_data' => $data,
                'errors' => $errors
            )
        );
        $this->getSession()->setStepData('osc_billing', $stepData);
        */
        $taskLog = $this->getSession()->getData('billing');
        if($taskLog == false)
            $taskLog = array();

        $taskLog = array_merge($taskLog, array(
                'success' => $success,
                //'previous_data' => $shippingData,
                'errors' => $errors
            )
        );

        $this->getSession()->setData('billing', $taskLog);
        //return $errors;

        return $errors;
    }
}