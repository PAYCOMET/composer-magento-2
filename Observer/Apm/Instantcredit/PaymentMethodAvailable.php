<?php

namespace Paycomet\Payment\Observer\Apm\Instantcredit;

use Paycomet\Payment\Model\Apm\Instantcredit\PaymentMethod;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class PaymentMethodAvailable implements ObserverInterface
{

    /**
     * @var \Paycomet\Payment\Helper\Apm\Instancredit\IcHelper
     */
    private $_icHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * PaymentMethodAvailable constructor.
     * @param \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper $icHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper $icHelper
    ) {
        $this->_icHelper = $icHelper;
        $this->_session = $session;
    }


    /**
     * @param Observer\Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if($observer->getEvent()->getMethodInstance()->getCode() == PaymentMethod::METHOD_CODE){
            $grandTotal = $this->_session->getQuote()->getGrandTotal();

            if (!$this->_icHelper->getIsEnabled() || !$this->_icHelper->isBetweenLimits($grandTotal)) {
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false); // Disable payment method at checkout page
            }
        }

    }

}