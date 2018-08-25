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
class Lotusbreath_OneStepCheckout_IndexController extends Lotusbreath_Checkout_Controller_Action
{

    protected $_isLoadedOneStepLayout = false;
    protected $_isRequireUpdateQuote = false;

    protected $_isCalculatedQuote = false;

    /**
     * Initial checkout process
     */
    public function initCheckout(){

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $countryCode = false;
        if ($customerAddressId = $quote->getCustomerId()) {
            $defaultShippingA = $quote->getCustomer()->getPrimaryShippingAddress();
            if ($defaultShippingA)
                $countryCode = $defaultShippingA->getCountryId();
        } else {
            $countryCode = $quote->getShippingAddress()->getCountryId();
            if(!$countryCode)
                $countryCode = Mage::getStoreConfig('lotusbreath_onestepcheckout/general/defaultcountry');
        }
        if ($countryCode) {
            $this->getOnepage()->getQuote()->getShippingAddress()->setCountryId($countryCode)->save();
            $this->getOnepage()->getQuote()->getBillingAddress()->setCountryId($countryCode)->save();
            $quote->getShippingAddress()->setCollectShippingRates(true);
            //$this->getQuote()->getShippingAddress()->setCollectShippingRates(false);
            $this->getCheckoutService()->saveShippingMethod();
        }
        $defaultPaymentCode = Mage::getStoreConfig('lotusbreath_onestepcheckout/general/defaultpayment');
        if (!$defaultPaymentCode) {
            $allActivePaymentMethods = Mage::getModel('payment/config')->getActiveMethods();
            foreach ($allActivePaymentMethods as $method => $methodInfo) {
                $defaultPaymentCode = $method;
                break;
            }
        }

        if($defaultPaymentCode && !$quote->getPayment()->getMethod()){
            $payment = $quote->getPayment();
            $payment->setMethod($defaultPaymentCode)->save();
        }

        $this->getCheckoutService()->updateQuote();

    }

    /**
     * use for all actions - pre-dispatch
     */
    public function preDispatch() {
        parent::preDispatch();
        $this->getSession()->compareAll();
        $helper = Mage::helper('checkout');
        $quote = $this->getOnepage()->getQuote();
        if (!Mage::helper('customer')->isLoggedIn()) {
            if ($helper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            }
            $billing = $this->getRequest()->getParam("billing");
            if ( !empty( $billing['create_new_account'] )) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);

            }elseif(!$helper->isAllowedGuestCheckout($quote)){
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
            }
            else{

                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            }
        }else{
            $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);

        }

        return $this;
    }

    public function indexAction()
    {

        Mage::dispatchEvent('controller_action_predispatch_onestepcheckout_index_index',
            array('request' => $this->getRequest(),
                'quote' => $this->getOnepage()->getQuote()));

        if (!Mage::getStoreConfig('lotusbreath_onestepcheckout/general/enabled')) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));

        $this->getOnepage()->initCheckout();
        //$this->getCheckoutService()->initCheckout();
        $this->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();

    }

    public function saveAction(){
        if($this->_expireAjax()){
            return false;
        }
        $tasks = $this->getRequest()->getParam('task', '');
        $tasks = explode(",", $tasks);
        //$this->getOnepage()->getQuote()->setTotalsCollectedFlag(false);
        $tasks[] = 'quote';
        $tasks = array_unique($tasks);
        $results = $this->getCheckoutService()->process($tasks);

        $this->_updateItems = explode(',', $this->getRequest()->getParam('relative_parts'));
        $this->_updateItems[] = 'totals_block';
        $previousData = $this->getRequest()->getPost();

        $return = array(
            //'update_items' => $updateItems,
            'results' => $results,
            'previous_data' => $previousData,
            'done_parts' => $results['done_parts']

        );

        $this->_return($return);

    }


    public function saveStepAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $step = $this->getRequest()->getParam('step', '');
        $updateItems = array();
        $previousData = $this->getRequest()->getPost();
        /*
         * do not collect totals if it's not necessary
         */
        $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false);

        switch ($step) {
            case 'shipping_method':
                $results = $this->process(
                    array(
                        'billing' => array(),
                        'shipping' => array(),
                        'shipping_method' => array(),
                        'payment' => array(),
                        'update_quote' => array('force' => true)
                    )
                );

                if (Mage::getStoreConfigFlag('lotusbreath_onestepcheckout/checkout_process/shipping_method_is_loading_payment')){
                    $updateItems[] = 'payment_block';
                }
                $updateItems[] = 'review_block';

                break;

            case 'payment_method':
                $results = $this->process(
                    array(
                        'shipping_method' => array(),
                        'payment' => array(),
                        'update_quote' => array('force' => true)
                    )
                );


                $updateItems[] = 'review_block';
                if (Mage::getStoreConfigFlag('lotusbreath_onestepcheckout/checkout_process/payment_is_loading_shipping_method')){
                    $updateItems[] = 'shipping_block';
                }
                break;
            case 'update_location_billing' :
                $results = $this->process(
                    array(
                        'billing' => array(),
                        'payment_method' => array(),
                        'update_quote' => array('force' => true)
                    )
                );

                $updateItems[] = 'review_block';
                $updateItems[] = 'payment_block';
                break;
            case  'update_location':
                $results = $this->process(
                    array(
                        'billing' => array(),
                        'shipping' => array(),
                        //'shipping_method' => array(),
                        'update_quote' => array('force' => true)
                    )
                );
                $updateItems[] = 'review_block';
                $updateItems[] = 'shipping_block';
                break;
            case 'update_location_billing_shipping':

                $results = $this->process(
                    array(
                        'billing' => array(),
                        'shipping' => array(),
                        //'shipping_method' => array('force' => false),
                        //'payment_method' => array('force' => false),
                        'update_quote' => array('force' => true)
                    )
                );

                $updateItems[] = 'review_block';
                $updateItems[] = 'shipping_block';
                $updateItems[] = 'payment_block';
                break;
            default :
                return;
        }
        $this->_updateItems = $updateItems;

        $return = array(
            //'update_items' => $updateItems,
            'results' => $results,
            'previous_data' => $previousData,

        );

        $this->_return($return);
    }





    public function savePostAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $updateItems = array();
        $previousData = $this->getRequest()->getPost();
        $results = $this->process(array(
            'billing' => array(),
            'shipping' => array(),
            'shipping_method' => array(),
            'payment' => array()
           ), true );
        $isHasErrors = false;
        foreach ($results as $stepIdx =>  $stepResult){
            if (!empty($stepResult['error'])){
                $isHasErrors = true;
                switch ($stepIdx){
                    case 'shipping_method':
                        $updateItems[] = 'shipping_block';
                        break;
                    case 'payment':
                        $updateItems[] = 'payment_block';
                        break;
                    case 'billing':
                        $updateItems[] = 'billing_block';
                        break;
                    case 'shipping':
                        $updateItems[] = 'shipping_address_block';
                        break;
                }
            }
        }
        if (!$isHasErrors){
            if (!empty($results['payment']['redirect'])) {
                //do not save order
                if ($data = $this->getRequest()->getPost('payment', false)) {
                    $this->getOnepage()->getQuote()->getPayment()->importData($data);
                }
                $this->getCheckoutService()->updateQuote();
            }else{
                $saveOrderResult = $this->getCheckoutService()->submitOrder();
                $results['save_order'] = $saveOrderResult;
                if ($saveOrderResult['success'] == false) {
                    $updateItems[] = "review_block";
                }
            }
        }else{
            $this->getCheckoutService()->updateQuote();
        }

        $return = array(
            'results' => $results,
            'previous_data' => $previousData,
            'update_items' => $updateItems,
            'success' => !empty($saveOrderResult['success']) ? $saveOrderResult['success'] : false,
            'step_data' => $this->getSession()->getSteps(),
            'error' => !empty($saveOrderResult['error']) ? $saveOrderResult['error'] : false,
        );
        if (count($updateItems)) {
            foreach ($updateItems as $updateItem) {
                $return['htmlUpdates'][$updateItem] = $this->_getUpdateItem($updateItem);
            }
        }
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($return));
    }

    protected function _getUpdateItem($itemName = null)
    {
        switch ($itemName) {
            case 'shipping_block':
                return $this->_getShippingMehodHtml();
            case 'payment_block':
                return $this->_getPaymentHtml();
            case 'review_block':
                return $this->_getReviewHtml();
            default:
                return '';
        }
    }


    protected function _getLocaleData($data)
    {
        $locationInfo = array();
        if ($data) {
            $locationInfo['country_id'] = !empty($data['country_id']) ? $data['country_id'] : null;
            $locationInfo['postcode'] = !empty($data['postcode']) ? $data['postcode'] : null;
            $locationInfo['region'] = !empty($data['region']) ? $data['region'] : null;;
            $locationInfo['region_id'] = !empty($data['region_id']) ? $data['region_id'] : null;
            $locationInfo['city'] = !empty($data['city']) ? $data['city'] : null;
        }
        return $locationInfo;

    }

    protected function _loadLayout()
    {

        if (!$this->_isLoadedOneStepLayout) {
            $this->loadLayout('lotusbreath_onestepcheckout_index_index');
            $this->_isLoadedOneStepLayout = true;
        }
    }

    protected function _getReviewHtml()
    {

        $this->_loadLayout();
        if ($reviewBlock = $this->getLayout()->getBlock('checkout.onepage.review')) {
            return $reviewBlock->toHtml();
        }
        return null;
    }

    protected function _getShippingMehodHtml()
    {
        $this->_loadLayout();
        if ($shippingMethodBlock = $this->getLayout()->getBlock('checkout.onepage.shipping_method')) {
            return $shippingMethodBlock->toHtml();
        }
        return null;
    }

    protected function _getPaymentHtml()
    {
        $this->_loadLayout();
        if ($paymentMethodBlock = $this->getLayout()->getBlock('checkout.onepage.payment')) {
            return $paymentMethodBlock->toHtml();
        }
        return null;
    }


    /**
     * Login action
     *
     */
    public function loginAction()
    {

        /*
        echo json_encode(array(
            'success' => true,
            'messages' => null
        ));
        exit;
        */

        if($this->_expireAjax()){
            return false;
        }


        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $errorMessages = array();
        $success = false;
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');

            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                    $success = true;
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    //$session->addError($message);
                    $session->setUsername($login['username']);
                    $errorMessages[] = $message;
                } catch (Exception $e) {
                    Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $errorMessages[] = $this->__('Login and password are required.');
                //$session->addError($this->__('Login and password are required.'));
            }
        }
        echo json_encode(array(
            'success' => $success,
            'messages' => $errorMessages
        ));

    }





    public function checkExistsEmailAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $email = $this->getRequest()->getParam('email', null);
        $response = array('success' => true, 'message' => '');
        if ($email) {
            if ($this->getOnepage()->customerEmailExists($email, Mage::app()->getWebsite()->getId())) {
                $response = array('success' => false, 'message' => '');
            }
        }
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($response));

    }



}