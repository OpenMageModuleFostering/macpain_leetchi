<?php

class Macpain_Leetchi_Model_Method_Leetchi extends Mage_Payment_Model_Method_Abstract {
    
    protected $_code = 'leetchi';
    protected $_formBlockType = 'macpain_leetchi/form_leetchi';
    protected $_module_debug_mode;
    protected $_order;
    protected $_url;

    # Leetchi redirect form fileds 
    protected $_leetchi_test_url;
    protected $_leetchi_prod_url;
    protected $_pay_to_email;
    protected $_status_url;
    protected $_return_url;
    protected $_cancel_url;
    protected $_transaction_amount;
    protected $_transaction_id;

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;
 
    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;
 
    /**
     * Can capture funds online?
     */
    protected $_canCapture              = true;
 
    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;
 
    /**
     * Can refund online?
     */
    protected $_canRefund               = false;
 
    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = true;
 
    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;
 
    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = false;
 
    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;
    
    public function __construct() {
        $this->_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $this->_leetchi_test_url = Mage::getStoreConfig("payment/leetchi/test_url");
    	$this->_leetchi_prod_url = Mage::getStoreConfig("payment/leetchi/prod_url");
    }

    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('leetchi/leetchi/redirect');
    }

    /**
     * Return the url for the getaway
     */
    public function getLeetchiUrl() {
        if ($this->getDebugMode())
            return $this->_leetchi_test_url;
        else
            return $this->_leetchi_prod_url;
    }

    /**
     * Get admin configuration for debug mode
     * @return int 
     */
    public function getDebugMode() {
        if ($this->_module_debug_mode == null) {
            $this->_module_debug_mode = Mage::getStoreConfig('payment/leetchi/test');
        }

        return $this->_module_debug_mode;
    }
    
    /**
     * Get admin configuration for merchant id
     * @return string 
     */
    public function getPayToEmail() {

        if ($this->_pay_to_email == null) {
            $this->_pay_to_email = Mage::getStoreConfig('payment/leetchi/merchant_email');
        }

        return $this->_pay_to_email;
    }
    
    /**
     * 
     * @return string 
     */
    public function getStatusUrl() {

        if ($this->_status_url == null) {
            $this->_status_url = $this->_url . Mage::getStoreConfig('payment/leetchi/statusurl');
        }

        return $this->_status_url;
    }
    
    /**
     * 
     * @return string 
     */
    public function getReturnUrl() {

        if ($this->_return_url == null) {
            $this->_return_url = $this->_url . Mage::getStoreConfig('payment/leetchi/returnurl');
        }

        return $this->_return_url;
    }
    
    /**
     * Get admin configuration for cancel url
     * @return string 
     */
    public function getCancelUrl() {

        if ($this->_cancel_url == null) {
            $this->_cancel_url = $this->_url . Mage::getStoreConfig('payment/leetchi/cancelurl');
        }

        return $this->_cancel_url;
    }
    
    /**
     * Get transaction amount for redirect form
     * @return string 
     */
    public function getTransactionAmount() {

        if ($this->_transaction_amount == null) {
            $this->_transaction_amount = $this->getOrder()->getGrandTotal() * 100;
        }

        return $this->_transaction_amount;
    }
    
    /**
     * Get transaction amount for redirect form
     * @return string 
     */
    public function getTransactionId() {

    	if ($this->_transaction_id == null) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_transaction_id = $order['increment_id'];
        }

        return $this->_transaction_id;
    }
    
    /**
     * Add all needed fields for leetchi payment
     * @param $forms Object
     */
    public function addLeetchiFields($form) {
        # add pay to email to redirect form
        $form->addField(
                "pay_to_email", 'hidden', array(
                    'name' => 'pay_to_email',
                    'value' => $this->getPayToEmail()
                )
        );
        
        # add status url to redirect form
        $form->addField(
                "status_url", 'hidden', array(
                    'name' => 'status_url',
                    'value' => $this->getStatusUrl()
                )
        );
        
        # add return url to redirect form
        $form->addField(
                "return_url", 'hidden', array(
                    'name' => 'return_url',
                    'value' => $this->getReturnUrl()
                )
        );
        
        # add cancel url to redirect form
        $form->addField(
                "cancel_url", 'hidden', array(
                    'name' => 'cancel_url',
                    'value' => $this->getCancelUrl()
                )
        );
        
        # add amount to redirect form
        $form->addField(
                "amount", 'hidden', array(
                    'name' => 'amount',
                    'value' => $this->getTransactionAmount()
                )
        );
        
        # add amount to redirect form
        $form->addField(
                "transaction_id", 'hidden', array(
                    'name' => 'transaction_id',
                    'value' => $this->getTransactionId()
                )
        );
        
        return $form;
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate() {
        parent::validate();

        return $this;
    }

    public function getCheckout() {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    public function getOrder() {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    }

}