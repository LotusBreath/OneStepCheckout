<?php
/*
Lotus Breath - One Step Checkout
Copyright (C) 2014  Lotus Breath
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Lotusbreath_OneStepCheckout_Controller_Action extends Mage_Core_Controller_Front_Action {

    protected $_updateItems = array();
    protected $_layoutHandle = 'lotusbreath_onestepcheckout_index_index';

    public function preDispatch() {
        if(!Mage::getStoreConfig('lotusbreath_onestepcheckout/general/enabled')){
            $this->_redirect(Mage::getUrl('checkout/onepage/index'));
        }

        /**
         * Disable some event for optimization
         */
        if(!Mage::getStoreConfig('lotusbreath_onestepcheckout/speedoptimizer/disablerssobserver')){
            $eventConfig = Mage::getConfig()->getEventConfig('frontend', 'sales_order_save_after');
            if ($eventConfig->observers->notifystock->class == 'rss/observer')
                $eventConfig->observers->notifystock->type = 'disabled';
            if ($eventConfig->observers->ordernew->class == 'rss/observer')
                $eventConfig->observers->ordernew->type = 'disabled';
        }
        /*
        if(!Mage::getStoreConfig('lotusbreath_onestepcheckout/speedoptimizer/disablevisitorlog')){

                   $eventConfig = Mage::getConfig()->getEventConfig('frontend', 'controller_action_predispatch');
                   $eventConfig->observers->log->type = 'disabled';
                   $eventConfig = Mage::getConfig()->getEventConfig('frontend', 'controller_action_postdispatch');
                   $eventConfig->observers->log->type = 'disabled';
                   $eventConfig = Mage::getConfig()->getEventConfig('frontend', 'sales_quote_save_after');
                   $eventConfig->observers->log->type = 'disabled';
                   $eventConfig = Mage::getConfig()->getEventConfig('frontend', 'checkout_quote_destroy');
                   $eventConfig->observers->log->type = 'disabled';

        }
        */
        parent::preDispatch();
        if (!$this->getRequest()->getParam('allow_gift_messages')){
            $this->getRequest()->setParam('giftmessage', false);
        }
        return $this;
        
    }
    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->getOnepage()->getQuote()->hasItems()
            || $this->getOnepage()->getQuote()->getHasError()
            || $this->getOnepage()->getQuote()->getIsMultiShipping()
        ) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true) )
        {
            $this->_ajaxRedirectResponse();
            return true;
        }
        return false;
    }

    protected function getOnepage()
    {
        return Mage::getSingleton('lotusbreath_onestepcheckout/type_osc');
    }

    /**
     * @return Lotusbreath_OneStepCheckout_Model_Session
     */
    protected function getSession()
    {
        return Mage::getSingleton('lotusbreath_onestepcheckout/session');
    }

    /**
     * Send Ajax redirect response
     *
     * @return Mage_Checkout_OnepageController
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    protected function getQuoteAddressService(){
        $quoteAddressService =  Mage::getSingleton("lotus_checkout/service_quote_address");
        $quoteAddressService->setQuote($this->getOnepage()->getQuote());
        return $quoteAddressService;
    }

    /**
     * @return Lotusbreath_Checkout_Model_Service_Checkout
     */
    protected function getCheckoutService(){
        $checkoutService =  Mage::getSingleton("lotus_checkout/service_checkout");
        $checkoutService->setOnepage($this->getOnepage());
        return $checkoutService;
    }


    public function setUpdatedItems($updatedItems = array()){
        $this->_updateItems = $updatedItems;
    }


    protected function _return($return){
        //$return
        $updateItems = new Varien_Object(
            array(
                'items' => $this->_updateItems
            )
        );
        //$data = array('controller' => $this, 'updated_items' => $updateItems);

        Mage::dispatchEvent('osc_before_return', array('controller' => $this, 'updated_items' => &$updateItems));

        $return['update_items'] = $updateItems->getItems();

        $return['htmlUpdates'] = $this->_renderBlocks($updateItems->getItems());

        if($return['update_items'] == 'all'){
            $return['update_items'] = array_keys($return['htmlUpdates']);
        }
        $return['step_data'] = $this->getSession()->getSteps();
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($return));
    }

    protected function _getDefinedBlocks(){

        $definedBlocks = array(
            'shipping_block' => "lotusbreath_onestepcheckout/layout_block_shipping_method",
            'payment_block' => "lotusbreath_onestepcheckout/layout_block_payment",
            'review_block' => "lotusbreath_onestepcheckout/layout_block_review",
            'totals_block' => "onestepcheckoutpro/layout_block_totals",
            'address_block' => "onestepcheckoutpro/layout_block_address"
        );
        return $definedBlocks;
    }

    protected function _renderBlocks($blocks = 'all'){

        $definedBlocks = $this->_getDefinedBlocks();
        $htmlUpdates = array();

        if($blocks === 'all'){
            $blocks = array_keys($definedBlocks);
        }

        if (!is_array($blocks)) {
            $blocks = array($blocks);
        }

        foreach ($blocks as $block) {
            if ($block && array_key_exists($block, $definedBlocks) ) {
                $modelClass = $definedBlocks[$block];
                //echo $modelClass;
                $modelBlock = Mage::getSingleton($modelClass);
                if($modelBlock){
                    $htmlUpdates[$block] = $modelBlock->toHtml($this->_layoutHandle);
                }
            }
        }
        return $htmlUpdates;
    }

    protected function _subscribeNewsletter()
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

                Mage::getModel('newsletter/subscriber')->subscribe($email);

            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
            catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function process($steps = null, $skipWhenError  = false){

        $result = $this->getCheckoutService()->process($steps);
        return $result;
    }

}
