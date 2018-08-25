<?php
class Lotusbreath_Checkout_Service_Task_Abstract
{
    protected function getQuoteAddressService(){
        $quoteAddressService =  Mage::getSingleton("lotus_checkout/service_quote_address");
        $quoteAddressService->setQuote($this->getQuote());
        return $quoteAddressService;
    }

    protected function _cpAddress($srcData, $destData){
        foreach($srcData as $key => $value){
            //if(!isset($destData) || empty($destData[$key])){
            $destData[$key] = $srcData[$key];
            //}
        }
        return $destData;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    protected function getOnepage(){
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * @return Mage_Core_Controller_Request_Http
     */
    protected function getRequest(){
        return Mage::app()->getRequest();
    }

    /**
     * @param $text
     * @return string
     */
    protected function __($text){
        return Mage::helper("lotusbreath_onestepcheckout")->__($text);
    }

    protected function getSession()
    {
        return Mage::getSingleton('lotus_checkout_service/web_session');
    }

}