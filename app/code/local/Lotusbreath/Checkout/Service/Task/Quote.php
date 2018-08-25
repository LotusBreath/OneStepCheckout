<?php
class Lotusbreath_Checkout_Service_Task_Quote extends Lotusbreath_Checkout_Service_Task_Abstract
{

    public function execute(){
        $this->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
        $this->getQuote()->save();
        return true;
    }
}