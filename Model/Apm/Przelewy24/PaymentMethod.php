<?php

namespace Paycomet\Payment\Model\Apm\Przelewy24;

class PaymentMethod extends \Paycomet\Payment\Model\Apm\PaymentMethod
{
    const METHOD_ID = 18;
    const METHOD_CODE = 'paycomet_przelewy24';

    protected $_code = self::METHOD_CODE;

    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canVoid = false;
    protected $_canReviewPayment = true;

}