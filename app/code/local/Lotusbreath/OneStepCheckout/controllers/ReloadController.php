<?php
class Lotusbreath_OneStepCheckout_ReloadController extends Lotusbreath_Checkout_Controller_Action
{

    public function indexAction()
    {
        if($this->_expireAjax()){
            return false;
        }

        $partials = $this->getRequest()->getParam('blocks', false);
        $return = array();
        $this->_updateItems = $partials;
        $this->_return($return);

    }

}