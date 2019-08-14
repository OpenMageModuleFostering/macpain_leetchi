<?php

class Macpain_Leetchi_Model_Leetchi extends Mage_Core_Model_Session_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->init('macpain_leetchi');
    }
}