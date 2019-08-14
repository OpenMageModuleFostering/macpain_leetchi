<?php

class Macpain_Leetchi_LeetchiController extends Mage_Core_Controller_Front_Action
{
	protected $_leetchi_response;
	protected $_md5sig;
	
	/**
	 * 
	 * Back process status action to validate leetchi payment response inforamtion
	 */
	public function statusAction()
	{
	    if ($this->getRequest()->isPost()) {
	        
	        try {
	        
		        $this->_setResponse($this->getRequest()->getParams());
		        
		        # TODO: Small piece of code to debug leetchi response
		        /*$response = '';
		        foreach ($this->getRequest()->getParams() as $key => $val) {
		            $response .= "'" . $key . "' => '" . $val . "', ";
		        }
		        Mage::log('Response form leetchi: ' . $response, null, 'leetchi_api.log');*/
		        
		        $order = Mage::getModel('sales/order');
		        $order->loadByIncrementId($this->_leetchi_response['transaction_id'])
		        	  ->sendNewOrderEmail();
		        
		        if ($this->_compareApiSigns()) {
		            
		            $order->addStatusToHistory(
		                $this->_getTransactionStatus($this->_leetchi_response['status']),
	                    $this->__('Customer successfully returned from Leetchi')
	                    . "\n<br>Leetchi transaction ID: " . $this->_leetchi_response['lma_transaction_id']
	                    . "\n<br>Pay to email: "   . $this->_leetchi_response['pay_to_email']
	                    . "\n<br>Pay from email: " . $this->_leetchi_response['pay_from_email']
		            );
		            
		            $order->getPayment()
			        	  ->getMethodInstance()
			              ->setTransactionId($this->_leetchi_response['transaction_id']);
		            
		        } else {
		            
		            $comment = $this->__('Order canceled. Error while processing leetchi data!');
		            $order->cancel()
		            	  ->addStatusToHistory($order::STATE_CANCELED, $comment, true)
		            	  ->sendOrderUpdateEmail(true, $comment);
		            
		        }
		        
		        $order->save();
	        
	        } catch (Exception $e) {
	            # Mage::log('Exception: ' . $e->getMessage(), null, 'leetchi_api.log');
	        }

	    }
	    
	}

	/**
	 * Cancel action
	 */
	public function cancelAction()
	{
	    $session = $this->_getCheckout();
	    
	    if ($session->getLastRealOrderId()) {
	        
	        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
	        if ($order->getId()) {
	            
	            # Cancel order
	            if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
	            	$comment = $this->__('Order was cancelled by customer.');
	                $order
	                	->registerCancellation($comment)
	                	->sendOrderUpdateEmail(true, $comment)
	                	->save();
	            }
	            
	            # Get quote
	            $quote = Mage::getModel('sales/quote')
	            	->load($order->getQuoteId());
	            
	            # Return quote
	            if ($quote->getId()) {
	                $quote->setIsActive(1)
		                  ->setReservedOrderId(NULL)
		                  ->save();
	                $session->replaceQuote($quote);
	            }
	            
	            # Unset data
	            $this->_emptyShoppingCart();
	            $session->unsLastRealOrderId();
	        }
	    }
	    
		$this
			->loadLayout()
			->_initLayoutMessages('checkout/session')
			->_initLayoutMessages('catalog/session')
	    	->renderLayout();
		
	}

	/**
	 * Redirect to leetchi.com action
	 */
	public function redirectAction()
	{
		$this
			->loadLayout()
    		->renderLayout();
    }
    
    /**
     * Empty customer's shopping cart
     */
    protected function _emptyShoppingCart()
    {
        try {
        	foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){
        		Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
        	}
            //$this->_getCart()->truncate()->save();
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot update shopping cart.'));
        }
    }
    
    /**
     * Get frontend checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
    
    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     *
     * Gets transaction statuc by response transaction status code
     * @param string $status
     */
    private function _getTransactionStatus($status)
    {
        $order_model = Mage::getModel('sales/order');
        switch ($status) {
        	case -2: # "failed";
        	    $status = $order_model::STATE_CANCELED; # TODO: Leetchi - this status is disabled for now
        	    break;
        	case -1: # "cancelled";
        	    $status = $order_model::STATE_CANCELED; # TODO: Leetchi - this status is disabled for now
        	    break;
        	case 0: # "pending";
        	    $status = $order_model::STATE_PENDING_PAYMENT; # TODO: Leetchi - this status is disabled for now
        	    break;
        	case 2: # "complete";
        	    $status = $order_model::STATE_PROCESSING;
        	    break;
        }
        return $status;
    }
    
    /**
     *
     * Sets response data and generates api sign
     * @param array $data
     */
    private function _setResponse($data)
    {
        if (count($data)) {
            $this->_leetchi_response = $data;
            $this->_setApiSign();
        }
        return $this;
    }
    
    /**
     *
     * Generate api sign from response and admin section secret word
     * @return string
     */
    private function _setApiSign()
    {
        $this->_md5sig = strtoupper(
        	md5(
            	$this->_leetchi_response['merchant_id']
                . $this->_leetchi_response['lma_transaction_id']
                . strtoupper(
                	md5($this->_getSecretWord())
                )
                . $this->_leetchi_response['lma_amount']
                . $this->_leetchi_response['status']
        	)
        );
        return $this->_md5sig;
    }
    
    /**
     *
     * Comapres generated sign with response md5sig
     * @return boolean
     */
    private function _compareApiSigns()
    {
        if ($this->_md5sig == $this->_leetchi_response['md5sig']) {
            return true;
        }
        return false;
    }
    
    /**
     *
     * Get secret word form admin section
     * @return string
     */
    private function _getSecretWord()
    {
        return Mage::getStoreConfig('payment/leetchi/api_pwd');
    }
    
}