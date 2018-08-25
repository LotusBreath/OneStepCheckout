<?php
class Lotusbreath_Checkout_Service_Task_Payment extends Lotusbreath_Checkout_Service_Task_Abstract
{


    public function execute()
    {
        $data = $this->getRequest()->getPost('payment');
        $success = true;
        if (empty($data['method'])){
            return false;
        }
        try {
            $result = $this->getOnepage()->savePayment($data);
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = 1;
            $result['message'] = $e->getMessage();
            $success = false;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = 1;
            $result['message'] = $e->getMessage();
            $success = false;
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = 1;
            $result['message'] = $this->__('Unable to set Payment Method.');
            $success = false;
        }
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        $taskLog = $this->getSession()->getData('payment');
        if($taskLog == false)
            $taskLog = array();
        $taskLog = array_merge($taskLog, array(
                'success' => $success,
                //'previous_data' => $shippingData,
                'errors' => $result
            )
        );

        $this->getSession()->setData('payment', $taskLog);

        return $result;

    }
}