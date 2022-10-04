<?php

namespace Paycomet\Payment\Model\Apm\Klarna;

class PaymentMethod extends \Paycomet\Payment\Model\Apm\PaymentMethod
{
    public const METHOD_ID = 13;
    public const METHOD_CODE = 'paycomet_klarna';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var boolean
     */
    protected $_canAuthorize = false;

    /**
     * @var boolean
     */
    protected $_canCapture = false;

    /**
     * @var boolean
     */
    protected $_canCapturePartial = false;

    /**
     * @var boolean
     */
    protected $_canCaptureOnce = false;

    /**
     * @var boolean
     */
    protected $_canRefund = true;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * @var boolean
     */
    protected $_canVoid = false;

    /**
     * @var boolean
     */
    protected $_canReviewPayment = true;
}
