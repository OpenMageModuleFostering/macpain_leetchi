<?php

class Macpain_Leetchi_Block_Form_Leetchi extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('macpain/leetchi/form/leetchi.phtml');
    }
    
    /**
     * Retrieve checkout session model object
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
}