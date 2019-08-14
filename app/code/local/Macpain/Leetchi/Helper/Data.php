<?php

class Macpain_Leetchi_Helper_Data extends Mage_Core_Helper_Data
{
    public function getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    public function getQuote()
    {
        return $this->getCart()->getQuote();
    }
}