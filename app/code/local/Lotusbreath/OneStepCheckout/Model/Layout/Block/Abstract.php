<?php
Class Lotusbreath_OneStepCheckout_Model_Layout_Block_Abstract implements Lotusbreath_OneStepCheckout_Model_Layout_Block_Interface {

    static $_layout = array();

    public function toHtml($handle = null){

        if(!$handle){
            $handle = $this->getDefaultHandle();
        }

        if($this->getBlockName()){
            if(!isset(self::$_layout[$handle])){
                $layout= Mage::getModel('core/layout');
                $layout->getUpdate()
                    ->addHandle('default')
                    ->addHandle($handle)
                    ->load();
                $layout->generateXml()
                    ->generateBlocks();
                self::$_layout[$handle] = $layout;
            }
            $layout = self::$_layout[$handle];

            $html = '';
            if($block = $layout->getBlock($this->getBlockName())){
                $html = $block->toHtml();
            }
            return $html;
        }

        return false;
    }
    public function getBlockName(){
        return false;
    }
    public function getDefaultHandle(){
        return false;
    }
}