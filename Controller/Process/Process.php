<?php

namespace Paycomet\Payment\Controller\Process;

class Process extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context     $context
     * @param \Magento\Checkout\Model\Session           $checkoutSession
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
        try {
            $order = $this->_checkoutSession->getLastRealOrder();
            $payment = $order->getPayment();

            // Verify is processed -> Exist AuthCode
            if (isset($payment) && $payment->getAdditionalInformation('DS_MERCHANT_AUTHCODE')) {
                return $this->_redirect('checkout/onepage/success');
            }

            // Verify is Challenge -> DS_CHALLENGE_URL
            if (isset($payment) && $payment->getAdditionalInformation('DS_CHALLENGE_URL')) {

                // Mantenemos el carrito
                /** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
                $quoteRepository = $this->_objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $quoteRepository->get($order->getQuoteId());
                $quote->setIsActive(1)->setReservedOrderId(null);
                $quoteRepository->save($quote);

                return $this->_redirect($payment->getAdditionalInformation('DS_CHALLENGE_URL'));
            }

            if (!$order->getId()) {
                return;
            }

            // End Token Payment verification
        } catch (\Exception $e) {
            return;
        }

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
