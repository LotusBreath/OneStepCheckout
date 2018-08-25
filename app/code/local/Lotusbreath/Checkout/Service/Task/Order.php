<?php
class Lotusbreath_Checkout_Service_Task_Order extends Lotusbreath_Checkout_Service_Task_Abstract
{

    public function execute(){
        try {

            $data = $this->getRequest()->getPost('payment', array());
            if ($data) {
                $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }

            //save comment
            if (Mage::getStoreConfig('lotusbreath_onestepcheckout/general/allowcomment')) {
                Mage::getSingleton('customer/session')->setOrderCustomerComment($this->getRequest()->getPost('order_comment'));
            }
            $this->subscribeNewsletter();
            $result = array();
            $dispatchParams = array(
                'data' => $this->getRequest()->getPost(),
                'result' => $result
            );

            Mage::dispatchEvent('lotus_checkout_submit_order_before',$dispatchParams);



            $this->getOnepage()->saveOrder();

            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error'] = false;

        } catch (Mage_Payment_Model_Info_Exception $e) {
            $message = $e->getMessage();
            $result['success'] = false;
            $result['error'] = true;
            if (!empty($message)) {
                $result['error_messages'] = $message;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
                $result['goto_section'] = $gotoSection;
                $this->getOnepage()->getCheckout()->setGotoSection(null);
            }

        } catch (Exception $e) {
            Mage::logException($e);
            //echo $e->getMessage();
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
        }
        $this->getOnepage()->getQuote()->save();
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }
        return $result;
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

                Mage::getModel('newsletter/subscriber')->subscribe($email);

            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
            catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}