<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paycomet\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public const PAYCOMET_SAVECARD     = 'saveCard';
    public const PAYCOMET_TOKENCARD    = 'paycometCard';
    public const PAYCOMET_JETTOKEN     = 'paycometJetToken';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYCOMET_SAVECARD,
        self::PAYCOMET_TOKENCARD,
        self::PAYCOMET_JETTOKEN
    ];

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            } else {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    ''
                );
            }
        }
    }
}
