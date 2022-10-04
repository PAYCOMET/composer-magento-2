<?php

namespace Paycomet\Payment\Controller\Process;

class SessionResult extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    private $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paycomet\Payment\Helper\Data         $helper
     * @param \Magento\Sales\Model\OrderFactory     $orderFactory
     * @param \Paycomet\Payment\Logger\Logger       $logger
     * @param \Magento\Checkout\Model\Session       $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paycomet\Payment\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Paycomet\Payment\Logger\Logger $logger,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_url = $context->getUrl();
        $this->_logger = $logger;
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {

        $response = $this->getRequest()->getParams();
        if (!$this->_validateResponse($response)) {
            $this->messageManager->addError(
                __('Your payment was unsuccessful. Please try again or use a different card / payment method.'),
                'PAYCOMET_messages'
            );
            $this->_redirect('checkout/cart');

            return;
        }

        $result = boolval($response['result']);
        if ($result) {
            $this->_session->getQuote()
                  ->setIsActive(false)
                  ->save();
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_cancel();
            $this->_session->setData(\Paycomet\Payment\Block\Process\Result\Observe::OBSERVE_KEY, '1');
            $this->messageManager->addError(
                __('Your payment was unsuccessful. Please try again or use a different card / payment method.'),
                'PAYCOMET_messages'
            );
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Validate Response
     *
     * @param array $response
     */
    private function _validateResponse($response)
    {
        if (!isset($response) || !isset($response['timestamp']) || !isset($response['order_id']) ||
            !isset($response['result']) || !isset($response['hash'])) {
            return false;
        }

        $timestamp = $response['timestamp'];
        $merchantid = $this->_helper->getConfigData('merchant_code');
        $orderid = $response['order_id'];
        $result = $response['result'];
        $hash = $response['hash'];
        $sha1hash = $this->_helper->signFields("$timestamp.$merchantid.$orderid.$result");

        //Check to see if hashes match or not
        if (strcmp($sha1hash, $hash) != 0) {
            return false;
        }
        $order = $this->_getOrder($orderid);

        return $order->getId();
    }

    /**
     * Cancel the order and restore the quote.
     */
    private function _cancel()
    {
        // restore the quote
        $this->_session->restoreQuote();

        $this->_helper->cancelOrder($this->_order);
    }

    /**
     * Get order based on increment_id.
     *
     * @param int $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder($incrementId)
    {
        if (!$this->_order) {
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }
}
