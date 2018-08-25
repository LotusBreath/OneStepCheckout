<?php
interface Lotusbreath_OneStepCheckout_Model_Layout_Block_Interface {

    public function toHtml($handle = null);
    public function getBlockName();
    public function getDefaultHandle();
}