<?php
class Lotusbreath_Checkout_Service_Task_Manager {

    protected $_tasks = array(
        'shipping' => 'lotus_checkout_service/task_shipping',
        'billing' => 'lotus_checkout_service/task_billing',
        'delivery' => 'lotus_checkout_service/task_delivery',
        'payment' => 'lotus_checkout_service/task_payment',
        'quote' => 'lotus_checkout_service/task_quote',
        /*for old onestepcheckout version version*/
        'shipping_method' => 'lotus_checkout_service/task_delivery',
        'update_quote' => 'lotus_checkout_service/task_quote',
        'order' => 'lotus_checkout_service/task_order',

    );

    public function getTaskService($code, $params = array()){
        if(isset($this->_tasks[$code])){
            $service = Mage::getModel($this->_tasks[$code], $params);
            return $service;
        }else{
            throw new Exception("Can not find any code service for ". $code);
        }
    }
}