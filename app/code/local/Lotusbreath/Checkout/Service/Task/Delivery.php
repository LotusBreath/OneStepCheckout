<?php
class Lotusbreath_Checkout_Service_Task_Delivery extends Lotusbreath_Checkout_Service_Task_Abstract{


    public function execute($shippingMethod = null){

        if($this->getQuote()->isVirtual()){
            return true;
        }

        $success = true;

        if(!$shippingMethod)
            $shippingMethod = $this->getRequest()->getPost('shipping_method', '');

        if(empty($shippingMethod)){
            $groupRates = $this->getQuote()->getShippingAddress()->getGroupedAllShippingRates();
            if(count($groupRates) == 1){
                $_sole = count($groupRates) == 1;
                $_rates = $groupRates[key($groupRates)];
                $_sole = $_sole && count($_rates) == 1;
                if ($_sole){
                    $shippingMethod = reset($_rates)->getCode();
                }
            }
        }

        if (empty($shippingMethod)) {
            $errors = array('error' => 1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
            $success = false;
        }

        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            $errors = array('error' => 1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
            $success = false;
        }
        if($success){
            $this->getQuote()->getShippingAddress()
                ->setShippingMethod($shippingMethod)->save();

            Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                array('request' => $this->getRequest(),
                    'quote' => $this->getQuote()));

        }

        $taskLog = $this->getSession()->getData('delivery');
        if($taskLog == false)
            $taskLog = array();

        $taskLog = array_merge($taskLog, array(
                'success' => $success,
                //'previous_data' => $shippingData,
                'errors' => $errors
            )
        );

        $this->getSession()->setData('delivery', $taskLog);
        return true;
    }
}