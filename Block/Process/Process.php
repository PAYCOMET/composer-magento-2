<?php

namespace Paytpv\Payment\Block\Process;

use Symfony\Component\Config\Definition\Exception\Exception;

class Process extends \Magento\Payment\Block\Form
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Order
     */
    private $_order;

    /**
     * @var \Paytpv\Payment\Helper\Data
     */
    private $_helper;

    /**
     * Process constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory                $orderFactory
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Paytpv\Payment\Helper\Data                  $helper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Paytpv\Payment\Helper\Data $helper,
        array $data = []
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        parent::__construct($context, $data);
        $this->_getOrder();
    }

    /**
     * @return string
     */
    public function getFormPaytpvUrl()
    {
        $result = '';
        try {
            $order = $this->_order;
            if ($order->getPayment()) {
                $result = $this->_order->getPayment()->getMethodInstance()->getFormPaytpvUrl();
            }
        } catch (Exception $e) {
            // do nothing for now
            throw($e);
        }

        return $result;
    }


    /**
     * Get order object.
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder()
    {
        if (!$this->_order) {
            $incrementId = $this->_getCheckout()->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }

    /**
     * Get frontend checkout session object.
     *
     * @return \Magento\Checkout\Model\Session
     */
    private function _getCheckout()
    {
        return $this->_checkoutSession;
    }
}
