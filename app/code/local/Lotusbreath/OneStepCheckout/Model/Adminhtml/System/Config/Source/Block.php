<?php
class Lotusbreath_OneStepCheckout_Model_Adminhtml_System_Config_Source_Block   {

    public function toOptionArray()
    {
        $helper = Mage::helper('lotusbreath_onestepcheckout');
        $blocks = Mage::getModel('cms/block')->getCollection()
            ->addFieldToFilter('is_active', 1);
        $options = array(
            array(
            'label' => $helper->__("None"),
            'value' => ''
            )
        );

        foreach($blocks as $block){
            $options[] = array(
                'value' => $block->getIdentifier(),
                'label' => $block->getTitle()
            );
        }
        return $options;

    }
}