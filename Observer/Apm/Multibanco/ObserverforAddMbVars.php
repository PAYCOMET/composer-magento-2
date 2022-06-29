<?php

namespace Paycomet\Payment\Observer\Apm\Multibanco;

use Paycomet\Payment\Model\Apm\Multibanco\PaymentMethod;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class ObserverforAddMbVars implements ObserverInterface
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    public function __construct(
        \Magento\Checkout\Model\Session $session
    ) {
        $this->_session = $session;
    }

    /**
     * @param Observer\Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getTransport();
        $order = $transport->getOrder();

        if($order->getPayment()->getMethodInstance()->getCode() == PaymentMethod::METHOD_CODE){
            $methodData = $order->getPayment()->getAdditionalInformation('METHOD_DATA');
            if ($methodData) {
                $methodData = json_decode($methodData);

                if ($methodData->entityNumber && $methodData->referenceNumber) {
                    $transport['paycomet_mb_entity'] = $methodData->entityNumber;
                    $transport['paycomet_mb_reference'] = $methodData->referenceNumber;
                    $transport['paycomet_img_src'] = '/img/apms/' . PaymentMethod::METHOD_CODE . '.png';
                }
            }
        }
    }

}