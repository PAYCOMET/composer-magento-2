<?php

namespace Paycomet\Payment\Model\Apm\Applepay;

use Paycomet\Payment\Model\Config\Source\PaymentAction;
use \Magento\Sales\Model\Order;
use Paycomet\Bankstore\ApiRest;


class PaymentMethod extends \Paycomet\Payment\Model\Apm\PaymentMethod
{
    public const METHOD_ID = 1;
    public const METHOD_CODE = 'paycomet_applepay';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var boolean
     */
    protected $_canAuthorize = true;

    /**
     * @var boolean
     */
    protected $_canCapture = true;

    /**
     * @var boolean
     */
    protected $_canCapturePartial = true;

    /**
     * @var boolean
     */
    protected $_canCaptureOnce = true;

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


    /**
     * Initialize
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $challengeUrl = "";
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation from PAYCOMET
        */
        $order->setCanSendNewEmailFlag(false);

       // New Card or Token Card with 3D SecurePayment
        // Initialize order to PENDING_PAYMENT


        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        return $this;
    }


    /**
     * Get Form Paycomet Url
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApplePayButtonInfo()
    {

        $order = $this->_session->getLastRealOrder();

        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key'));

        $payment_action     = trim($this->_helper->getConfigData('payment_action'));

        $dcc                = $this->_helper->getConfigData('dcc');

        $realOrderId = $order->getRealOrderId();
        //$fieldOrderId = $realOrderId.'_'.$timestamp;
        $fieldOrderId = $realOrderId;

        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);
        $customerId = $order->getCustomerId();

        $shopperLocale = $this->_resolver->getLocale();
        $language_data = explode("_", $shopperLocale);
        $language = $language_data[0];

        /** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId(null);
        $quoteRepository->save($quote);

        $Secure = 1;

        // 1 ->EXECUTE_PURCHASE : 3->CREATE_PREAUTORIZATION
        $OPERATION = ($payment_action==PaymentAction::AUTHORIZE_CAPTURE)?1:3;

        // DCC OPERATION
        if ($OPERATION == 1 && $dcc) {
            $OPERATION = 116;
        }

        $function_txt = "";

        $dataResponse = [];

        if ($api_key != "") {

            $merchantData = $this->_helper->getMerchantData($order, self::METHOD_ID);

            $paymentData = [
                'terminal' => $merchant_terminal,
                'order' => $fieldOrderId,
                'amount' => $amount,
                'currency' => $orderCurrencyCode,
                'methodId' => 1,
                'originalIp' => $this->_remoteAddress->getRemoteAddress(),
                'userInteraction' => 1,
                'merchantData' => $merchantData,
                'urlOk' => $this->_helper->getURLOK($order),
                'urlKo' => $this->_helper->getURLKO($order),
                'secure' => $Secure,
                'notifyDirectPayment'   => 1
            ];

            try {
                $apiRest = new ApiRest($api_key);
                $response = $apiRest->applePayButtonInfo(
                    $OPERATION,
                    $language,
                    $merchant_terminal,
                    $paymentData
                );

                if (($response->errorCode ?? null)==0) {
                    return $response;
                }
            } catch (\Exception $e) {
                $dataResponse["success"]  = 0;
                $dataResponse["error"]  = $response->errorCode;
            }
        } else {
            $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
            $dataResponse["success"]  = 0;
            $dataResponse["error"]  = 1004;
        }
        return $dataResponse;


    }
 }
