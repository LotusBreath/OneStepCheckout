<?php
class Lotusbreath_Checkout_Model_Service_Checkout extends Varien_Object{

    protected $_processedJobs = array();
    /**
     * @return Lotus_Checkout_Model_Service_Quote_Address
     */
    protected function getQuoteAddressService(){
        $quoteAddressService =  Mage::getSingleton("lotus_checkout/service_quote_address");
        $quoteAddressService->setQuote($this->getQuote());
        return $quoteAddressService;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
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

    /**
     * @return Lotusbreath_OneStepCheckout_Model_Session
     */

    protected function getSession()
    {
        return Mage::getSingleton('lotusbreath_onestepcheckout/session');
    }

    /**
     * @todo Process all jobs [shipping,billing,shipping_method,payment,totals calculationg]
     * @param $jobs
     * @return array
     */
    public function process($jobs = array()){
        $this->_processedJobs = array();
        $result = array();
        $this->getQuote()->setTotalsCollectedFlag(true);
        foreach ($jobs as $idx => $job){
            if(is_array($job)){
                $jobIdx = $idx;
            }else{
                $jobIdx = $job;
            }

            $this->_processedJobs[] = $jobIdx;
            $taskManager = new Lotusbreath_Checkout_Service_Task_Manager();
            $service = $taskManager->getTaskService($jobIdx);
            $result[$jobIdx] = $service->execute();
            $this->afterSaveProcessJob($jobIdx);
        }
        $webSession = Mage::getSingleton('lotus_checkout_service/web_session');
        $result['done_parts'] = array();
        $shipping = $webSession->getData('shipping');
        $billing = $webSession->getData('billing');
        if(isset($shipping['success']) && $shipping['success'] == true){
            if(isset($billing['success']) && $billing['success'] == true){
                $result['done_parts'][] = 'part-address';
            }

        }
        $delivery = $webSession->getData('delivery');
        if(isset($delivery['success']) && $delivery['success'] == true){
            $result['done_parts'][] = 'part-delivery';
        }
        return $result;
    }


    public function saveShippingMethod($shippingMethod = false){
        $taskManager = new Lotusbreath_Checkout_Service_Task_Manager();
        $service = $taskManager->getTaskService('delivery');
        return $service->execute($shippingMethod);
    }

    public function updateQuote(){
        $taskManager = new Lotusbreath_Checkout_Service_Task_Manager();
        $service = $taskManager->getTaskService('quote');
        return $service->execute();
    }

    public function afterSaveProcessJob($jobIndex){
        if($jobIndex == 'shipping'){
            //collect shipping rates again
            $this->getQuote()->getShippingAddress()
                ->setCollectShippingRates(true)
                //->collectShippingRates()
                //->save()
            ;
        }
    }


    public function submitOrder()
    {
        $taskManager = new Lotusbreath_Checkout_Service_Task_Manager();
        /**
         * @var Lotusbreath_Checkout_Service_Task_Order $service
         */
        $service = $taskManager->getTaskService('order');
        return $service->execute();

    }



    protected function subscribeNewsletter()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('newsletter')) {
            $customerSession = Mage::getSingleton('customer/session');

            if ($customerSession->isLoggedIn())
                $email = $customerSession->getCustomer()->getEmail();
            else {
                $bill_data = $this->getRequest()->getPost('billing');
                $email = $bill_data['email'];
            }
            try {
                if (!$customerSession->isLoggedIn() && Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1)
                    Mage::throwException($this->__('Sorry, subscription for guests is not allowed. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));
                $ownerId = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email)->getId();
                if ($ownerId !== null && $ownerId != $customerSession->getId())
                    Mage::throwException($this->__('Sorry, you are trying to subscribe email assigned to another user.'));
                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                return $status;
            } catch (Mage_Core_Exception $e) {
                return false;
            }
            catch (Exception $e) {
                return false;
            }
        }
    }


}