<?php
class Lotusbreath_OneStepCheckout_CouponController extends Lotusbreath_Checkout_Controller_Action
{

    public function applyCouponAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $this->process(
            array(
                'billing' => array(),
                'shipping' => array(),
            )
        );

        $saveCouponResult = array();
        $quote = $this->getOnepage()->getQuote();
        $couponCode = (string)$this->getRequest()->getParam('coupon_code');
        if ($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $quote->getCouponCode();

        if ( !strlen($couponCode) && !strlen($oldCouponCode) ) {
            $saveCouponResult['success'] = false;
            $saveCouponResult['message'] = Mage::helper('checkout')->__('Coupon code is required');
        }
        try {

            $codeLength = strlen($couponCode);
            $isCodeLengthValid = true;
            if (defined(Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH)) {
                $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;
            }

            $checkoutSession = Mage::getSingleton('checkout/session');
            $checkoutSession->setCartWasUpdated(true);


            $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
            $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
            //$this->getOnepage()->getQuote()->save();


            if (strlen($couponCode)) {
                if ($isCodeLengthValid && $couponCode == $quote->getCouponCode()) {

                    $saveCouponResult['success'] = true;
                    $saveCouponResult['message'] = Mage::helper('checkout/cart')->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
                } else {
                    $saveCouponResult['success'] = false;
                    $saveCouponResult['message'] = Mage::helper('checkout/cart')->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                }
            } else {
                $saveCouponResult['success'] = true;
                $saveCouponResult['message'] = Mage::helper('checkout/cart')->__('Coupon code was canceled.');
            }
        } catch (Mage_Core_Exception $e) {
            //$this->_getSession()->addError($e->getMessage());
            $saveCouponResult['success'] = false;
            $saveCouponResult['message'] = $e->getMessage();

        } catch (Exception $e) {
            $saveCouponResult['success'] = false;
            $saveCouponResult['message'] = Mage::helper('checkout/cart')->__('Cannot apply the coupon code.');
            Mage::logException($e);
        }
        $this->setUpdatedItems(array('review_block','payment_block','shipping_block'));
        $return = array(
            'results' => $saveCouponResult,
        );
        $this->_return($return);
    }

}