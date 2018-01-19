<?php

namespace Paytpv\Payment\Controller\Process;

class Process extends \Magento\Framework\App\Action\Action
{


    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Paytpv\Payment\Helper\Data                          $helper
     * @param \Magento\Sales\Model\OrderFactory                    $orderFactory
     * @param \Magento\Framework\Registry                          $coreRegistry
     * @param \Paytpv\Payment\Logger\Logger                        $logger
     * @param \Paytpv\Payment\API\PaytpvPaymentManagementInterface $paymentManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * Set redirect.
     */
    public function execute()
    {
        // If Token Payment and AuthCode -> got to Success
        try{
            $order = $this->_checkoutSession->getLastRealOrder();
            $payment = $order->getPayment();
           
            if (isset($payment) && $payment->getAdditionalInformation('DS_MERCHANT_AUTHCODE')){
                return $this->_redirect('checkout/onepage/success');
            }

            if (!$order->getId()){
                return;
            }
            
            // End Token Payment verification
        }catch (\Exception $e){ 
            return;
        }
        
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();

    }
}
