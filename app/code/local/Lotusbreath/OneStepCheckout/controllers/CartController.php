<?php
class Lotusbreath_OneStepCheckout_CartController extends Lotusbreath_Checkout_Controller_Action
{

    public function updateCartAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $this->getCheckoutService()->saveShippingMethod();
        $this->getCheckoutService()->savePayment();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $cartData = $this->getRequest()->getParam('cart');
        if (is_array($cartData)) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = Mage::getSingleton('checkout/cart');
            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)
                ->save();
        }
        $checkoutSession->setCartWasUpdated(true);

        $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
        $this->getOnepage()->getQuote()->save();
        $this->setUpdatedItems(array('review_block', 'shipping_block', 'payment_block'));
        $return = array(
            'results' => true,
        );
        $this->_return($return);
    }

    public function clearCartItemAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $id = (int)$this->getRequest()->getPost('id');
        if ($id) {
            $cart = Mage::getSingleton('checkout/cart');
            $checkoutSession = Mage::getSingleton('checkout/session');
            try {
                $cart->removeItem($id)
                    ->save();
                $checkoutSession->setCartWasUpdated(true);
                //$this->_requireUpdateQuote();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                Mage::logException($e);
            }

        }

        if ($cart && $cart->getQuote()->getItemsCount() == 0) {
            $return = array(
                'results' => false,
                'cart_is_empty' => true,
            );
        } else {
            $this->setUpdatedItems(array('review_block', 'shipping_block', 'payment_block'));
            $return = array(
                'results' => true,

            );
        }

        $this->_return($return);
    }

}