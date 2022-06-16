<?php

namespace Paycomet\Payment\Block\Info;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Paycomet_Payment::info/info.phtml';

    /**
     * Prepare Paycomet related payment info.
     *
     * @param \Magento\Framework\DataObject|array $transport
     *
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $orderId = $this->getInfo()->getAdditionalInformation('Order');
        if ($orderId=="")
            $orderId = $this->getInfo()->getAdditionalInformation('DS_MERCHANT_ORDER');

        $cardType = $this->getInfo()->getAdditionalInformation('CardBrand');
        if ($cardType=="")
            $cardType = $this->getInfo()->getAdditionalInformation('DS_MERCHANT_CARDBRAND');

        $cardCountry = $this->getInfo()->getAdditionalInformation('CardCountry');
        if ($cardCountry=="")
            $cardCountry = $this->getInfo()->getAdditionalInformation('DS_MERCHANT_CARDCOUNTRY');


        $bicCode = $this->getInfo()->getAdditionalInformation('BicCode');
        $ErrorID = $this->getInfo()->getAdditionalInformation('ErrorID');
        if ($ErrorID=="")
            $ErrorID = $this->getInfo()->getAdditionalInformation('DS_ERROR_ID');


        $ErrorDescription = $this->getInfo()->getAdditionalInformation('ErrorDescription');

        $Currency = $this->getInfo()->getAdditionalInformation('Currency');
        if ($Currency=="")
            $Currency = $this->getInfo()->getAdditionalInformation('DS_MERCHANT_CURRENCY');


        $cardDigits = $this->getInfo()->getAdditionalInformation('CARDDIGITS');

        $result = $this->getInfo()->getAdditionalInformation('Response');
        if ($result=="")
            $result = $this->getInfo()->getAdditionalInformation('DS_RESPONSE');

        $authCode = $this->getInfo()->getAdditionalInformation('AuthCode');
        if ($authCode=="")
            $authCode = $this->getInfo()->getAdditionalInformation('DS_MERCHANT_AUTHCODE');

        $Scoring = $this->getInfo()->getAdditionalInformation('Scoring');
        $SecurePayment = $this->getInfo()->getAdditionalInformation('SecurePayment');

        $MethodName = $this->getInfo()->getAdditionalInformation('MethodName');

        $methodData = $this->getInfo()->getAdditionalInformation('METHOD_DATA');

        $data = $this->checkAndSet($data, $orderId, 'Order Id');
        $data = $this->checkAndSet($data, $MethodName, 'Method');
        $data = $this->checkAndSet($data, $cardType, 'Card Type');
        $data = $this->checkAndSet($data, $cardCountry, 'Card Country');
        $data = $this->checkAndSet($data, $Currency, 'Currency');
        $data = $this->checkAndSet($data, $bicCode, 'Bic Code');
        $data = $this->checkAndSet($data, $result, 'Result');
        $data = $this->checkAndSet($data, $authCode, 'Auth Code');
        $data = $this->checkAndSet($data, $ErrorID, 'Error Id');
        if ($ErrorID) {
            $data = $this->checkAndSet($data, $ErrorDescription, 'Error Description');
        }

        $data = $this->checkAndSet($data, $Scoring, 'Fraud Filter Result');

        if ($cardDigits) {
            $data[(string) __('Card Number')] = sprintf('xxxx-%s', $cardDigits);
        }

        if ($SecurePayment)
            $data[(string) __('3D Secure Status')] = $this->_is3DSecure($SecurePayment);

        if ($methodData){
            $methodData = json_decode($methodData);
                        
            if ($methodData->entityNumber) {
                $data = $this->checkAndSet($data, $methodData->entityNumber, 'Entity');
            }
            if ($methodData->referenceNumber) {
                $data = $this->checkAndSet($data, $methodData->referenceNumber, 'Reference');
            }
            
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    private function checkAndSet($data, $field, $text)
    {
        if ($field) {
            $data[(string) __($text)] = $field;
        }

        return $data;
    }

    private function _is3DSecure($SecurePayment)
    {
        if ($SecurePayment)
            return '3D Secure';
        else
            return 'Not 3D Secure';

    }
}