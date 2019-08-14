<?php

class Macpain_Leetchi_Block_Redirect extends Mage_Core_Block_Template {

    protected function _construct() {
        parent::_construct();
    }

    protected function _toHtml() {
        $leetchi = Mage::getModel('macpain_leetchi/method_leetchi');
        $form = new Varien_Data_Form();
        $form->setAction($leetchi->getLeetchiUrl())
             ->setId('leetchi_payment_form')
             ->setName('leetchi_payment_form')
             ->setMethod('POST')
             ->setUseContainer(true);

        $form = $leetchi->addLeetchiFields($form);

        $this->setLeetchiForm($form);

        return parent::_toHtml();
    }

}

?>
