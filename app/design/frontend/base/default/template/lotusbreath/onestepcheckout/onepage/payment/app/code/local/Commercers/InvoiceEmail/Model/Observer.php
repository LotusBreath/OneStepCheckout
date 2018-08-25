<?php
class Commercers_InvoiceEmail_Model_Observer {

    public function sendInvoiceEmail($observer){
        $order = $observer->getEvent()->getOrder();

        /**
         * @var Mage_Sales_Model_Order_Invoice $invoice
         */
        $invoices = Mage::getModel('sales/order_invoice')->getCollection()
            ->addAttributeToFilter('order_id', array('eq'=>$order->getId()));

        if($invoices->getSize()){
            foreach($invoices as $invoice){
                if(!$invoice->getEmailSent())
                    $invoice->sendEmail(true);
            }
        }


    }
}