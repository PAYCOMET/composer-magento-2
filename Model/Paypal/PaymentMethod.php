<?php

namespace Paycomet\Payment\Model\Paypal;

use Paycomet\Payment\Model\Config\Source\DMFields;
use Paycomet\Payment\Model\Config\Source\FraudMode;
use Paycomet\Payment\Model\Config\Source\PaymentAction;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Paycomet\Bankstore\Client;
use Paycomet\Bankstore\ApiRest;
use Paycomet\Payment\Observer\DataAssignObserver;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;


class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    const METHOD_CODE = 'paycomet_paypal';
    const NOT_AVAILABLE = 'N/A';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'guest';
    /**
     * @var CUSTOMER_ID , used when order is placed by customers
     */
    const CUSTOMER_ID = 'customer';

    /**
     * @var string
     */
    protected $_infoBlockType = 'Paycomet\Payment\Block\Info\Info';

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_urlBuilder;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $_resolver;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    private $_paycometLogger;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $_resourceInterface;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    private $_objectManager;

    private $_remoteAddress;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                      $request
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Paycomet\Payment\Helper\Data                              $helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                  $resolver
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Paycomet\Payment\Logger\Logger                            $paycometLogger
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                  $resourceInterface
     * @param \Magento\Checkout\Model\Session                              $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param \Magento\Framework\ObjectManagerInterface                    $objectmanager,
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Paycomet\Payment\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Paycomet\Payment\Logger\Logger $paycometLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        RemoteAddress $remoteAddress,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );


        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_resolver = $resolver;
        $this->_request = $request;
        $this->_paycometLogger = $paycometLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
        $this->_objectManager = $objectmanager;
        $this->_remoteAddress = $remoteAddress;
    }

    /**
     * Do not validate payment form using server methods
     *
     * @return bool
     */
    public function validate()
    {
        return true;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation from PAYCOMET
         */
        $order->setCanSendNewEmailFlag(false);

        $orderCurrencyCode = $order->getBaseCurrencyCode();

        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);

        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount'));
        }

        $order = $payment->getOrder();

        $tokenCardPayment = false; // Inicializamos

        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_TOKENCARD);
        $jetToken = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_JETTOKEN);
        // Pago mediante tarjeta tokenizada ----------------------------------------------------------
        if (isset($hash) && $hash!=""){

            // Verifiy Token Data
            $data = $this->_helper->getTokenData($payment);
            if (!isset($data["iduser"]) || !isset($data["tokenuser"])){
                throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
            }
            $idUser = $data["iduser"];
            $tokenUser = $data["tokenuser"];
            $tokenCardPayment = true;

        // Fin Pago mediante tarjeta tokenizada ------------------------------------------------------
        } if (isset($jetToken) && $jetToken!=""){

            $merchant_code      = trim($this->_helper->getConfigData('merchant_code'));
            $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
            $merchant_pass      = trim($this->_helper->getEncryptedConfigData('merchant_pass'));
            $jet_id            = trim($this->_helper->getEncryptedConfigData('jetid'));
            $api_key            = trim($this->_helper->getEncryptedConfigData('api_key'));

             // Uso de Rest
            if ($api_key != "") {

                try {
                    $apiRest = new ApiRest($api_key);
                    $tokenCard = $apiRest->addUser(
                        $merchant_terminal,
                        $jetToken,
                        '',
                        '',
                        '',
                        2
                    );
                    $response = array();
                    $response["DS_RESPONSE"] = ($tokenCard->errorCode > 0)? 0 : 1;

                    if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                    }

                    if ($response["DS_RESPONSE"]==1) {
                        $response["DS_IDUSER"] = $tokenCard->idUser ?? 0;
                        $response["DS_TOKEN_USER"] = $tokenCard->tokenUser ?? '';
                    }

                } catch (Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                }
            } else {

                $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,$jet_id);

                $response = $ClientPaycomet->AddUserToken($jetToken);
                if (!isset($response) || !$response) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                }
                $response = (array) $response;
                if ($response["DS_ERROR_ID"] >0 ) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                }
            }

            $idUser = $response["DS_IDUSER"];
            $tokenUser = $response["DS_TOKEN_USER"];

            $tokenCardPayment = true;

        }

        // Fin Pago mediante jetIFrame -------------------------------------------------------------------

        // If a Payment with Saved Card or JetIframe
        if ($tokenCardPayment){

            // Si es pago con Token o jetIframe guardamos los datos del token en el pedido
            $order->setPaycometToken($idUser."|".$tokenUser);
            $order->save();

            // Verifiy 3D Secure Payment
            $Secure = ($this->isSecureTransaction($order,$amount))?1:0;

            // Non Secure Payment with Token Card
            if (!$Secure){

                $payment_action = trim($this->_helper->getConfigData('payment_action'));
                switch ($payment_action){
                    case PaymentAction::AUTHORIZE_CAPTURE:
                        $this->capture($payment,$order->getBaseGrandTotal());
                        break;
                    case PaymentAction::AUTHORIZE:
                        $this->authorize($payment,$order->getBaseGrandTotal());
                        break;
                }

                // Set order as PROCESSING
                $stateObject->setState(Order::STATE_PROCESSING);
                $stateObject->setStatus(Order::STATE_PROCESSING);

                return $this;
            }
        }

        // New Card or Token Card with 3D SecurePayment
        // Initialize order to PENDING_PAYMENT
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        return $this;
    }


    /**
     * Send authorize request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param  float $amount
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount.'));
        }

        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();
        $currencyCode = $order->getBaseCurrencyCode();

        $amount = $this->_helper->amountFromMagento($amount, $currencyCode);

        //CREATE_PREAUTHORIZATION
        $data = $this->_helper->getTokenData($payment);
        if (!isset($data["iduser"]) || !isset($data["tokenuser"])){
            throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
        }
        $IdUser = $data["iduser"];
        $TokenUser = $data["tokenuser"];

        $merchant_code      = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass      = trim($this->_helper->getEncryptedConfigData('merchant_pass'));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key'));

        $methodId = 1;
        $secure = 0;
        $userInteraction = 1;
        $defered = 0;

        // Uso de Rest
        if ($api_key != "") {

            $merchantData = $this->getMerchantData($order);

            try {
                $apiRest = new ApiRest($api_key);
                $createPreauthorizationResponse = $apiRest->createPreautorization(
                    $merchant_terminal,
                    $realOrderId,
                    $amount,
                    $currencyCode,
                    $methodId,
                    $this->_remoteAddress->getRemoteAddress(),
                    $secure,
                    $IdUser,
                    $TokenUser,
                    $this->getURLOK($order),
                    $this->getURLKO($order),
                    '',
                    '',
                    '',
                    $userInteraction,
                    [],
                    '',
                    '',
                    $merchantData,
                    $defered
                );
                $response = array();
                $response["DS_RESPONSE"] = ($createPreauthorizationResponse->errorCode > 0)? 0 : 1;
                $response["DS_ERROR_ID"] = $createPreauthorizationResponse->errorCode;

                if ($response["DS_RESPONSE"]==1) {
                    $response["DS_MERCHANT_AUTHCODE"] = $createPreauthorizationResponse->authCode ?? '';
                    $response["DS_MERCHANT_AMOUNT"] = $createPreauthorizationResponse->amount ?? 0;
                }

                $response["DS_CHALLENGE_URL"] = $createPreauthorizationResponse->challengeUrl ?? '';

                // Si nos llega challenge se la asignamos para redirigir posteriormente
                if ($response["DS_CHALLENGE_URL"] != "" && $response["DS_CHALLENGE_URL"] != "0") {
                    $payment->setAdditionalInformation("DS_CHALLENGE_URL", $response["DS_CHALLENGE_URL"]);
                    return $this;
                }

            } catch (Exception $e) {
                $response["DS_RESPONSE"] = 0;
                $response["DS_ERROR_ID"] = $createPreauthorizationResponse->errorCode;
            }

        } else {

            $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

            $response = $ClientPaycomet->CreatePreauthorization($IdUser, $TokenUser, $amount, $realOrderId, $currencyCode,"","",null,"",null,null,null,null,$userInteraction);

            if (!isset($response) || !$response) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action failed'));
            }
            $response = (array) $response;

            // Si nos llega challenge se la asignamos para redirigir posteriormente
            if ($response["DS_CHALLENGE_URL"] != "" && $response["DS_CHALLENGE_URL"] != "0") {

                $order->setPaycometToken($IdUser."|".$TokenUser);
                $order->save();
                $payment->setAdditionalInformation("DS_CHALLENGE_URL", urldecode($response["DS_CHALLENGE_URL"]));
                return $this;
            }
        }

        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment failed. Error ( %s ) - %s', $response['DS_ERROR_ID'], $this->_helper->getErrorDesc($response['DS_ERROR_ID'])))
            );
        }

        // Add Extra Data to Response
        $errorDesc = $this->_helper->getErrorDesc($response['DS_ERROR_ID']);

        $response["ErrorDescription"] = (string)$errorDesc;
        $response["SecurePayment"] = 0;

        // Set Operation Type
        $response["TransactionType"] = 3;

        $this->_helper->CreateTransInvoice($order,$response);

        return $this;

    }



    /**
     * Capture.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount.'));
        }

        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();
        $currencyCode = $order->getBaseCurrencyCode();

        $amount = $this->_helper->amountFromMagento($amount, $currencyCode);
        $merchant_code      = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass      = trim($this->_helper->getEncryptedConfigData('merchant_pass'));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key'));

        $TransactionType = $payment->getAdditionalInformation('TransactionType');
        switch ($TransactionType){

            case 3: //PREAUTHORIZATION_CONFIRM

                // Uso de Rest
                if ($api_key != "") {

                    try {
                        $apiRest = new ApiRest($api_key);

                        $AuthCode = str_replace("-capture","",$payment->getTransactionId());

                        $confirmPreautorization = $apiRest->confirmPreautorization(
                            $realOrderId,
                            $merchant_terminal,
                            $amount,
                            $this->_remoteAddress->getRemoteAddress(),
                            $AuthCode,
                            '0'
                        );

                        $response = array();
                        $response["DS_RESPONSE"] = ($confirmPreautorization->errorCode > 0)? 0 : 1;
                        $response["DS_ERROR_ID"] = $confirmPreautorization->errorCode;

                        if ($response["DS_RESPONSE"]==1) {
                            $response["DS_MERCHANT_AUTHCODE"] = $confirmPreautorization->authCode;
                            $response["DS_MERCHANT_AMOUNT"] = $confirmPreautorization->amount;
                        }

                    } catch (Exception $e) {
                        $response["DS_RESPONSE"] = 0;
                        $response["DS_ERROR_ID"] = $confirmPreautorization->errorCode;
                    }

                } else {

                    $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

                    $paycometToken = explode("|",$order->getPaycometToken());
                    $IdUser = $paycometToken[0];
                    $TokenUser = $paycometToken[1];

                    $response = $ClientPaycomet->PreauthorizationConfirm($IdUser, $TokenUser, $amount, $realOrderId);

                    if (!isset($response) || !$response) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The capture action failed'));
                    }

                    $response = (array) $response;

                }

                if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                         __(sprintf('Payment failed. Error ( %s ) - %s', $response['DS_ERROR_ID'], $this->_helper->getErrorDesc($response['DS_ERROR_ID'])))

                    );
                }

                $payment->setTransactionId($response['DS_MERCHANT_AUTHCODE'])
                        ->setTransactionApproved(true)
                        ->setParentTransactionId($payment->getAdditionalInformation($response['DS_MERCHANT_AUTHCODE']));

                // Add Extra Data to Response
                $errorDesc = $this->_helper->getErrorDesc($response['DS_ERROR_ID']);
                $response["ErrorDescription"] = (string)$errorDesc;
                $response["SecurePayment"] = 0;

                $this->_helper->setAdditionalInfo($payment, $response);

                break;

            default: // EXECUTE_PURCHASE

                $data = $this->_helper->getTokenData($payment);
                if (!isset($data["iduser"]) || !isset($data["tokenuser"])){
                    throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
                }
                $IdUser = $data["iduser"];
                $TokenUser = $data["tokenuser"];

                $methodId = 1;
                $secure = 0;
                $userInteraction = 1;
                $notifyDirectPayment = 1;


                // Uso de Rest
                if ($api_key != "") {

                    $merchantData = $this->getMerchantData($order);
                    $apiRest = new ApiRest($api_key);

                    try {

                        $executePurchaseResponse = $apiRest->executePurchase(
                            $merchant_terminal,
                            $realOrderId,
                            $amount,
                            $currencyCode,
                            $methodId,
                            $this->_remoteAddress->getRemoteAddress(),
                            $secure,
                            $IdUser,
                            $TokenUser,
                            $this->getURLOK($order),
                            $this->getURLKO($order),
                            '',
                            '',
                            '',
                            $userInteraction,
                            [],
                            '',
                            '',
                            $merchantData,
                            $notifyDirectPayment
                        );

                        $response = array();

                        $response["DS_RESPONSE"] = ($executePurchaseResponse->errorCode > 0)? 0 : 1;
                        $response["DS_ERROR_ID"] = $executePurchaseResponse->errorCode;
                        if ($response["DS_RESPONSE"]==1) {
                            $response["DS_MERCHANT_AUTHCODE"] = $executePurchaseResponse->authCode ?? '';
                            $response["DS_MERCHANT_AMOUNT"] = $executePurchaseResponse->amount ?? 0;
                        }
                        $response["DS_CHALLENGE_URL"] = $executePurchaseResponse->challengeUrl ?? '';

                        // Si nos llega challenge se la asignamos para redirigir posteriormente
                        if ($response["DS_CHALLENGE_URL"] != "" && $response["DS_CHALLENGE_URL"] != "0") {
                            $payment->setAdditionalInformation("DS_CHALLENGE_URL", $response["DS_CHALLENGE_URL"]);
                            return $this;
                        }
                    } catch (Exception $e) {
                        $response["DS_RESPONSE"] = 0;
                        $response["DS_ERROR_ID"] = $executePurchaseResponse->errorCode;
                    }

                } else {
                    $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

                    $response = $ClientPaycomet->ExecutePurchase($IdUser,$TokenUser,$amount,$realOrderId,$currencyCode,"","",null,"",null,null,null,null,$userInteraction);

                    if (!isset($response) || !$response) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The capture action failed'));
                    }
                    $response = (array) $response;

                    // Si nos llega challenge se la asignamos para redirigir posteriormente
                    if ($response["DS_CHALLENGE_URL"] != "" && $response["DS_CHALLENGE_URL"] != "0") {
                        $payment->setAdditionalInformation("DS_CHALLENGE_URL", urldecode($response["DS_CHALLENGE_URL"]));
                        return $this;
                    }
                }

                if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {

                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(sprintf('Payment failed. Error ( %s ) - %s', $response['DS_ERROR_ID'], $this->_helper->getErrorDesc($response['DS_ERROR_ID'])))
                    );
                }

                // Add Extra Data to Response
                $errorDesc = $this->_helper->getErrorDesc($response['DS_ERROR_ID']);
                $response["ErrorDescription"] = (string)$errorDesc;
                $response["SecurePayment"] = 0;

                $this->_helper->CreateTransInvoice($order,$response);

                break;
        }

        return $this;

    }


    /**
     * Refund specified amount for payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);
        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($amount, $orderCurrencyCode);
        $AuthCode = $payment->getTransactionId();
        $AuthCode = str_replace("-refund","",$AuthCode);
        $AuthCode = str_replace("-capture","",$AuthCode);
        $storeId = $order->getStoreId();

        $merchant_code      = trim($this->_helper->getConfigData('merchant_code',$storeId));
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal',$storeId));
        $merchant_pass      = trim($this->_helper->getEncryptedConfigData('merchant_pass',$storeId));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key',$storeId));

        // Uso de Rest
        if ($api_key != "") {

            $notifyDirectPayment = 2;
            $apiRest = new ApiRest($api_key);

            $executeRefundReponse = $apiRest->executeRefund(
                $realOrderId,
                $merchant_terminal,
                $amount,
                $orderCurrencyCode,
                $AuthCode,
                $this->_remoteAddress->getRemoteAddress(),
                $notifyDirectPayment
            );

            $response = array();
            $response["DS_RESPONSE"] = ($executeRefundReponse->errorCode > 0)? 0 : 1;
            $response["DS_ERROR_ID"] = $executeRefundReponse->errorCode;

            if ($response["DS_RESPONSE"]==1) {
                $response["DS_MERCHANT_AUTHCODE"] = $executeRefundReponse->authCode;
            }


        } else {

            $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");
            $response = $ClientPaycomet->ExecuteRefund('', '', $realOrderId, $orderCurrencyCode, $AuthCode, $amount);

            if (!isset($response) || !$response) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The refund action failed'));
            }

            $response = (array) $response;
        }
        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Refund failed. Error ( %s ) - %s', $response['DS_ERROR_ID'], $this->_helper->getErrorDesc($response['DS_ERROR_ID'])))
            );
        } else {
            $payment->setTransactionId($response['DS_MERCHANT_AUTHCODE'])
                    ->setParentTransactionId($AuthCode)
                    ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $response);
        }
        return $this;
    }

    /**
     * Refund specified amount for payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {

        parent::void($payment);
        $order = $payment->getOrder();

        $realOrderId = $order->getRealOrderId();

        $orderCurrencyCode = $order->getBaseCurrencyCode();

        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);


        $paycometToken = explode("|",$order->getPaycometToken());
        $IdUser = $paycometToken[0];
        $TokenUser = $paycometToken[1];

        $AuthCode = $payment->getTransactionId();


        $storeId = $order->getStoreId();

        $merchant_code = $this->_helper->getConfigData('merchant_code',$storeId);
        $merchant_terminal = $this->_helper->getConfigData('merchant_terminal',$storeId);
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass',$storeId);

        $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");


        $response = $ClientPaycomet->PreauthorizationCancel($IdUser, $TokenUser, $amount, $realOrderId);

        if (!isset($response) || !$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The cancel action failed'));
        }

        $response = (array) $response;
        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Refund failed. Error ( %s ) - %s', $response['DS_ERROR_ID'], $this->_helper->getErrorDesc($response['DS_ERROR_ID'])))
            );
        }
        $payment->setTransactionId($response['DS_MERCHANT_AUTHCODE'])
                ->setParentTransactionId($AuthCode)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $response);

        return $this;
    }

    /**
     * Accept under review payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new \Magento\Framework\Exception\LocalizedException(__('Acept Paaym'));
        return $this;
    }

    public function hold(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new \Magento\Framework\Exception\LocalizedException(__('Hold Paaym'));
        return $this;
    }




    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject|mixed $data
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $additionalData = $data->getAdditionalData();
        $infoInstance = $this->getInfoInstance();

        return $this;
    }

    public function getCheckoutCards()
    {
        $data = array();
        // Si no esta logado no cargamos nada
        if (!$this->_helper->customerIsLogged())
            return $data;

        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();


        $select = $connection->select()
            ->from(
                ['token' => 'paycomet_token'],
                ['hash', 'cc', 'brand' , 'desc']
            )
            ->where('customer_id = ?', $this->_helper->getCustomerId())
            ->order('date DESC');
        $data = $connection->fetchAll($select);
        return $data;
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/process/process',
            ['_secure' => $this->_getRequest()->isSecure()]
        );
    }


    /**
     * Retrieve request object.
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function _getRequest()
    {
        return $this->_request;
    }

    /**
     * Post request to gateway and return response.
     *
     * @param DataObject      $request
     * @param ConfigInterface $config
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        // Do nothing
        $this->_helper->logDebug('Gateway postRequest called');
    }


    private function isSecureTransaction($order,$total_amount=0){


        $terminales = trim($this->_helper->getConfigData('merchant_terminales'));
        $secure_first = trim($this->_helper->getConfigData('secure_first'));
        $secure_amount = trim($this->_helper->getConfigData('secure_amount'));

        $payment  = $order->getPayment();

        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_TOKENCARD);
        $jetIframe = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_JETTOKEN);

        $paymentNewCard = true;
        // Si pago con Token y NO pago con jetIframe
        if ( (isset($hash) && $hash!="") || !($jetIframe!="")) {
            $paymentNewCard = false;
        }

        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);

        if ($secure_amount>0) {
            $secure_amount = $this->_helper->amountFromMagento($secure_amount, $orderCurrencyCode);
        }

        // Transaccion Segura:
        // Si solo tiene Terminal Seguro
        if ($terminales==0){
            return true;
        }
        // Si esta definido que el pago es 3d secure y no estamos usando una tarjeta tokenizada
        if ($secure_first && $paymentNewCard){
            return true;
        }

        $total_amount = ($total_amount==0)?$amount:$total_amount;

        // Si se supera el importe maximo para compra segura
        if ($terminales==2 && ($secure_amount!="" && $secure_amount < $total_amount)){
            return true;
        }

        // Si esta definido como que la primera compra es Segura y es la primera compra aunque este tokenizada
        if ($terminales==2 && $secure_first && !$paymentNewCard && $this->_helper->isFirstPurchaseToken($order->getPayment()))
            return true;

        return false;
    }


    private function getMerchantData($order)
    {

        $MERCHANT_EMV3DS = $this->getEMV3DS($order);
		$SHOPPING_CART = $this->getShoppingCart($order);

        $datos = array_merge($MERCHANT_EMV3DS,$SHOPPING_CART);

        return $datos;
    }



    private function isoCodeToNumber($code)
    {
        $isoCodeNumber = 724; // Default value;
		$arrCode = array("AF" => "004", "AX" => "248", "AL" => "008", "DE" => "276", "AD" => "020", "AO" => "024", "AI" => "660", "AQ" => "010", "AG" => "028", "SA" => "682", "DZ" => "012", "AR" => "032", "AM" => "051", "AW" => "533", "AU" => "036", "AT" => "040", "AZ" => "031", "BS" => "044", "BD" => "050", "BB" => "052", "BH" => "048", "BE" => "056", "BZ" => "084", "BJ" => "204", "BM" => "060", "BY" => "112", "BO" => "068", "BQ" => "535", "BA" => "070", "BW" => "072", "BR" => "076", "BN" => "096", "BG" => "100", "BF" => "854", "BI" => "108", "BT" => "064", "CV" => "132", "KH" => "116", "CM" => "120", "CA" => "124", "QA" => "634", "TD" => "148", "CL" => "52", "CN" => "156", "CY" => "196", "CO" => "170", "KM" => "174", "KP" => "408", "KR" => "410", "CI" => "384", "CR" => "188", "HR" => "191", "CU" => "192", "CW" => "531", "DK" => "208", "DM" => "212", "EC" => "218", "EG" => "818", "SV" => "222", "AE" => "784", "ER" => "232", "SK" => "703", "SI" => "705", "ES" => "724", "US" => "840", "EE" => "233", "ET" => "231", "PH" => "608", "FI" => "246", "FJ" => "242", "FR" => "250", "GA" => "266", "GM" => "270", "GE" => "268", "GH" => "288", "GI" => "292", "GD" => "308", "GR" => "300", "GL" => "304", "GP" => "312", "GU" => "316", "GT" => "320", "GF" => "254", "GG" => "831", "GN" => "324", "GW" => "624", "GQ" => "226", "GY" => "328", "HT" => "332", "HN" => "340", "HK" => "344", "HU" => "348", "IN" => "356", "ID" => "360", "IQ" => "368", "IR" => "364", "IE" => "372", "BV" => "074", "IM" => "833", "CX" => "162", "IS" => "352", "KY" => "136", "CC" => "166", "CK" => "184", "FO" => "234", "GS" => "239", "HM" => "334", "FK" => "238", "MP" => "580", "MH" => "584", "PN" => "612", "SB" => "090", "TC" => "796", "UM" => "581", "VG" => "092", "VI" => "850", "IL" => "376", "IT" => "380", "JM" => "388", "JP" => "392", "JE" => "832", "JO" => "400", "KZ" => "398", "KE" => "404", "KG" => "417", "KI" => "296", "KW" => "414", "LA" => "418", "LS" => "426", "LV" => "428", "LB" => "422", "LR" => "430", "LY" => "434", "LI" => "438", "LT" => "440", "LU" => "442", "MO" => "446", "MK" => "807", "MG" => "450", "MY" => "458", "MW" => "454", "MV" => "462", "ML" => "466", "MT" => "470", "MA" => "504", "MQ" => "474", "MU" => "480", "MR" => "478", "YT" => "175", "MX" => "484", "FM" => "583", "MD" => "498", "MC" => "492", "MN" => "496", "ME" => "499", "MS" => "500", "MZ" => "508", "MM" => "104", "NA" => "516", "NR" => "520", "NP" => "524", "NI" => "558", "NE" => "562", "NG" => "566", "NU" => "570", "NF" => "574", "NO" => "578", "NC" => "540", "NZ" => "554", "OM" => "512", "NL" => "528", "PK" => "586", "PW" => "585", "PS" => "275", "PA" => "591", "PG" => "598", "PY" => "600", "PE" => "604", "PF" => "258", "PL" => "616", "PT" => "620", "PR" => "630", "GB" => "826", "EH" => "732", "CF" => "140", "CZ" => "203", "CG" => "178", "CD" => "180", "DO" => "214", "RE" => "638", "RW" => "646", "RO" => "642", "RU" => "643", "WS" => "882", "AS" => "016", "BL" => "652", "KN" => "659", "SM" => "674", "MF" => "663", "PM" => "666", "VC" => "670", "SH" => "654", "LC" => "662", "ST" => "678", "SN" => "686", "RS" => "688", "SC" => "690", "SL" => "694", "SG" => "702", "SX" => "534", "SY" => "760", "SO" => "706", "LK" => "144", "SZ" => "748", "ZA" => "710", "SD" => "729", "SS" => "728", "SE" => "752", "CH" => "756", "SR" => "740", "SJ" => "744", "TH" => "764", "TW" => "158", "TZ" => "834", "TJ" => "762", "IO" => "086", "TF" => "260", "TL" => "626", "TG" => "768", "TK" => "772", "TO" => "776", "TT" => "780", "TN" => "788", "TM" => "795", "TR" => "792", "TV" => "798", "UA" => "804", "UG" => "800", "UY" => "858", "UZ" => "860", "VU" => "548", "VA" => "336", "VE" => "862", "VN" => "704", "WF" => "876", "YE" => "887", "DJ" => "262", "ZM" => "894", "ZW" => "716");

        if (isset($arrCode[$code])) {
            $isoCodeNumber = $arrCode[$code];
        }
        return $isoCodeNumber;

    }

    private function isoCodePhonePrefix($code)
    {
        $isoCodePhonePrefix = 34;
        $arrCode = array("AC" => "247", "AD" => "376", "AE" => "971", "AF" => "93","AG" => "268", "AI" => "264", "AL" => "355", "AM" => "374", "AN" => "599", "AO" => "244", "AR" => "54", "AS" => "684", "AT" => "43", "AU" => "61", "AW" => "297", "AX" => "358", "AZ" => "374", "AZ" => "994", "BA" => "387", "BB" => "246", "BD" => "880", "BE" => "32", "BF" => "226", "BG" => "359", "BH" => "973", "BI" => "257", "BJ" => "229", "BM" => "441", "BN" => "673", "BO" => "591", "BR" => "55", "BS" => "242", "BT" => "975", "BW" => "267", "BY" => "375", "BZ" => "501", "CA" => "1", "CC" => "61", "CD" => "243", "CF" => "236", "CG" => "242", "CH" => "41", "CI" => "225", "CK" => "682", "CL" => "56", "CM" => "237", "CN" => "86", "CO" => "57", "CR" => "506", "CS" => "381", "CU" => "53", "CV" => "238", "CX" => "61", "CY" => "392", "CY" => "357", "CZ" => "420", "DE" => "49", "DJ" => "253", "DK" => "45", "DM" => "767", "DO" => "809", "DZ" => "213", "EC" => "593", "EE" => "372", "EG" => "20", "EH" => "212", "ER" => "291", "ES" => "34", "ET" => "251", "FI" => "358", "FJ" => "679", "FK" => "500", "FM" => "691", "FO" => "298", "FR" => "33", "GA" => "241", "GB" => "44", "GD" => "473", "GE" => "995", "GF" => "594", "GG" => "44", "GH" => "233", "GI" => "350", "GL" => "299", "GM" => "220", "GN" => "224", "GP" => "590", "GQ" => "240", "GR" => "30", "GT" => "502", "GU" => "671", "GW" => "245", "GY" => "592", "HK" => "852", "HN" => "504", "HR" => "385", "HT" => "509", "HU" => "36", "ID" => "62", "IE" => "353", "IL" => "972", "IM" => "44", "IN" => "91", "IO" => "246", "IQ" => "964", "IR" => "98", "IS" => "354", "IT" => "39", "JE" => "44", "JM" => "876", "JO" => "962", "JP" => "81", "KE" => "254", "KG" => "996", "KH" => "855", "KI" => "686", "KM" => "269", "KN" => "869", "KP" => "850", "KR" => "82", "KW" => "965", "KY" => "345", "KZ" => "7", "LA" => "856", "LB" => "961", "LC" => "758", "LI" => "423", "LK" => "94", "LR" => "231", "LS" => "266", "LT" => "370", "LU" => "352", "LV" => "371", "LY" => "218", "MA" => "212", "MC" => "377", "MD"  > "533", "MD" => "373", "ME" => "382", "MG" => "261", "MH" => "692", "MK" => "389", "ML" => "223", "MM" => "95", "MN" => "976", "MO" => "853", "MP" => "670", "MQ" => "596", "MR" => "222", "MS" => "664", "MT" => "356", "MU" => "230", "MV" => "960", "MW" => "265", "MX" => "52", "MY" => "60", "MZ" => "258", "NA" => "264", "NC" => "687", "NE" => "227", "NF" => "672", "NG" => "234", "NI" => "505", "NL" => "31", "NO" => "47", "NP" => "977", "NR" => "674", "NU" => "683", "NZ" => "64", "OM" => "968", "PA" => "507", "PE" => "51", "PF" => "689", "PG" => "675", "PH" => "63", "PK" => "92", "PL" => "48", "PM" => "508", "PR" => "787", "PS" => "970", "PT" => "351", "PW" => "680", "PY" => "595", "QA" => "974", "RE" => "262", "RO" => "40", "RS" => "381", "RU" => "7", "RW" => "250", "SA" => "966", "SB" => "677", "SC" => "248", "SD" => "249", "SE" => "46", "SG" => "65", "SH" => "290", "SI" => "386", "SJ" => "47", "SK" => "421", "SL" => "232", "SM" => "378", "SN" => "221", "SO" => "252", "SO" => "252", "SR"  > "597", "ST" => "239", "SV" => "503", "SY" => "963", "SZ" => "268", "TA" => "290", "TC" => "649", "TD" => "235", "TG" => "228", "TH" => "66", "TJ" => "992", "TK" =>  "690", "TL" => "670", "TM" => "993", "TN" => "216", "TO" => "676", "TR" => "90", "TT" => "868", "TV" => "688", "TW" => "886", "TZ" => "255", "UA" => "380", "UG" =>  "256", "US" => "1", "UY" => "598", "UZ" => "998", "VA" => "379", "VC" => "784", "VE" => "58", "VG" => "284", "VI" => "340", "VN" => "84", "VU" => "678", "WF" => "681", "WS" => "685", "YE" => "967", "YT" => "262", "ZA" => "27","ZM" => "260", "ZW" => "263");

        if (isset($arrCode[$code])) {
            $isoCodePhonePrefix = $arrCode[$code];
        }
        return $isoCodePhonePrefix;
    }


    private function getEMV3DS($order)
    {

        $s_cid = $order->getCustomerId();
        if ($s_cid == "" ) {
            $s_cid = 0;
        }

        $Merchant_EMV3DS = array();

        $billingAddressData = $order->getBillingAddress();
        $phone = "";
        if (!empty($billingAddressData))   $phone = $billingAddressData->getTelephone();


        $Merchant_EMV3DS["customer"]["id"] = (int)$s_cid;
		$Merchant_EMV3DS["customer"]["name"] = ($order->getCustomerFirstname())?$order->getCustomerFirstname():$billingAddressData->getFirstname();
		$Merchant_EMV3DS["customer"]["surname"] = ($order->getCustomerLastname())?$order->getCustomerLastname():$billingAddressData->getLastname();
		$Merchant_EMV3DS["customer"]["email"] = $order->getCustomerEmail();

        $shippingAddressData = $order->getShippingAddress();
        if ($shippingAddressData) {
            $streetData = $shippingAddressData->getStreet();
            $street0 = (isset($streetData[0]))? strtolower($streetData[0]) : "";
            $street1 = (isset($streetData[1]))? strtolower($streetData[1]) : "";
            $street2 = (isset($streetData[2]))? strtolower($streetData[2]) : "";
        }

        if ($phone!="") {
            $phone_prefix = $this->isoCodePhonePrefix($billingAddressData->getCountryId());
	        if ($phone_prefix!="") {
                $arrDatosWorkPhone["cc"] = substr(preg_replace("/[^0-9]/", '', $phone_prefix),0,3);
                $arrDatosWorkPhone["subscriber"] = substr(preg_replace("/[^0-9]/", '', $phone),0,15);
                $Merchant_EMV3DS["customer"]["workPhone"] = $arrDatosWorkPhone;
            }
        }

        $Merchant_EMV3DS["customer"]["firstBuy"] = ($this->_helper->getFirstOrder($order) == 0)?"no":"si";

        $Merchant_EMV3DS["shipping"]["shipAddrCity"] = ($shippingAddressData)?$shippingAddressData->getCity():"";
        $Merchant_EMV3DS["shipping"]["shipAddrCountry"] = ($shippingAddressData)?$shippingAddressData->getCountryId():"";

        if ($Merchant_EMV3DS["shipping"]["shipAddrCountry"]!="") {
            $Merchant_EMV3DS["shipping"]["shipAddrCountry"] = (int)$this->isoCodeToNumber($Merchant_EMV3DS["shipping"]["shipAddrCountry"]);
        }

        $Merchant_EMV3DS["shipping"]["shipAddrLine1"] = ($shippingAddressData)?$street0:"";
        $Merchant_EMV3DS["shipping"]["shipAddrLine2"] = ($shippingAddressData)?$street1:"";
        $Merchant_EMV3DS["shipping"]["shipAddrLine3"] = ($shippingAddressData)?$street2:"";
        $Merchant_EMV3DS["shipping"]["shipAddrPostCode"] = ($shippingAddressData)?$shippingAddressData->getPostcode():"";
        //$Merchant_EMV3DS["shipping"]["shipAddrState"] = ($shippingAddressData)?$shippingAddressData->getRegionId():"";	 // ISO 3166-2

        // Billing
        if ($billingAddressData) {
            $streetData = $billingAddressData->getStreet();
            $street0 = (isset($streetData[0]))? strtolower($streetData[0]) : "";
            $street1 = (isset($streetData[1]))? strtolower($streetData[1]) : "";
            $street2 = (isset($streetData[2]))? strtolower($streetData[2]) : "";
        }

        $Merchant_EMV3DS["billing"]["billAddrCity"] = ($billingAddressData)?$billingAddressData->getCity():"";
        $Merchant_EMV3DS["billing"]["billAddrCountry"] = ($billingAddressData)?$billingAddressData->getCountryId():"";
        if ($Merchant_EMV3DS["billing"]["billAddrCountry"]!="") {
            $Merchant_EMV3DS["billing"]["billAddrCountry"] = (int)$this->isoCodeToNumber($Merchant_EMV3DS["billing"]["billAddrCountry"]);
        }
        $Merchant_EMV3DS["billing"]["billAddrLine1"] = ($billingAddressData)?$street0:"";
        $Merchant_EMV3DS["billing"]["billAddrLine2"] = ($billingAddressData)?$street1:"";
        $Merchant_EMV3DS["billing"]["billAddrLine3"] = ($billingAddressData)?$street2:"";
        $Merchant_EMV3DS["billing"]["billAddrPostCode"] = ($billingAddressData)?$billingAddressData->getPostcode():"";
        //$Merchant_EMV3DS["billing"]["billAddrState"] = ($billingAddressData)?$billingAddressData->getRegion():"";     // ISO 3166-2


        // acctInfo
		$Merchant_EMV3DS["acctInfo"] = $this->acctInfo($order);

		// threeDSRequestorAuthenticationInfo
        $Merchant_EMV3DS["threeDSRequestorAuthenticationInfo"] = $this->threeDSRequestorAuthenticationInfo();


        // AddrMatch
        if ($order->getBillingAddress() && $order->getShippingAddress()) {
		    $Merchant_EMV3DS["addrMatch"] = ($order->getBillingAddress()->getData('customer_address_id') == $order->getShippingAddress()->getData('customer_address_id'))?"Y":"N";
        }

		$Merchant_EMV3DS["challengeWindowSize"] = 05;


        return $Merchant_EMV3DS;

    }


    private function acctInfo($order)
    {

		$acctInfoData = array();
		$date_now = new \DateTime("now");

		$isGuest = $order->getCustomerIsGuest();
		if ($isGuest) {
			$acctInfoData["chAccAgeInd"] = "01";
		} else {

            $customer = $this->_helper->getCustomerById($order->getCustomerId());
			$date_customer = new \DateTime( $customer->getCreatedAt());

			$diff = $date_now->diff($date_customer);
			$dias = $diff->days;

			if ($dias==0) {
				$acctInfoData["chAccAgeInd"] = "02";
			} else if ($dias < 30) {
				$acctInfoData["chAccAgeInd"] = "03";
			} else if ($dias < 60) {
				$acctInfoData["chAccAgeInd"] = "04";
			} else {
				$acctInfoData["chAccAgeInd"] = "05";
            }

            $accChange = new \DateTime($customer->getUpdatedAt());
            $acctInfoData["chAccChange"] = $accChange->format('Ymd');

            $date_customer_upd = new \DateTime($customer->getUpdatedAt());
            $diff = $date_now->diff($date_customer_upd);
            $dias_upd = $diff->days;

            if ($dias_upd==0) {
                $acctInfoData["chAccChangeInd"] = "01";
            } else if ($dias_upd < 30) {
                $acctInfoData["chAccChangeInd"] = "02";
            } else if ($dias_upd < 60) {
                $acctInfoData["chAccChangeInd"] = "03";
            } else {
                $acctInfoData["chAccChangeInd"] = "04";
            }


            $chAccDate = new \DateTime($customer->getCreatedAt());
            $acctInfoData["chAccDate"] = $chAccDate->format('Ymd');

            $acctInfoData["nbPurchaseAccount"] = $this->numPurchaseCustomer($order->getCustomerId(),1,6,"month");
            //$acctInfoData["provisionAttemptsDay"] = "";

            $acctInfoData["txnActivityDay"] = $this->numPurchaseCustomer($order->getCustomerId(),0,1,"day");
            $acctInfoData["txnActivityYear"] = $this->numPurchaseCustomer($order->getCustomerId(),0,1,"year");


            if ($order->getShippingAddress()) {
                $firstAddressDelivery = $this->firstAddressDelivery($order->getCustomerId(),$order->getShippingAddress()->getData('customer_address_id'));

                if ($firstAddressDelivery!="") {

                    $acctInfoData["shipAddressUsage"] = date("Ymd",strtotime($firstAddressDelivery));

                    $date_firstAddressDelivery = new \DateTime($firstAddressDelivery);
                    $diff = $date_now->diff($date_firstAddressDelivery);
                    $dias_firstAddressDelivery = $diff->days;

                    if ($dias_firstAddressDelivery==0) {
                        $acctInfoData["shipAddressUsageInd"] = "01";
                    } else if ($dias_upd < 30) {
                        $acctInfoData["shipAddressUsageInd"] = "02";
                    } else if ($dias_upd < 60) {
                        $acctInfoData["shipAddressUsageInd"] = "03";
                    } else {
                        $acctInfoData["shipAddressUsageInd"] = "04";
                    }
                }
            }

        }

        if ( $order->getShippingAddress() &&
            (
                ( ($order->getCustomerFirstname() != "") && ( $order->getCustomerFirstname() != $order->getShippingAddress()->getData('firstname') ) ) ||
                ( ($order->getCustomerLastname() != "") && ( $order->getCustomerLastname() != $order->getShippingAddress()->getData('lastname') ) )
            )
        ) {
            $acctInfoData["shipNameIndicator"] = "02";
        } else {
            $acctInfoData["shipNameIndicator"] = "01";
        }

        $acctInfoData["suspiciousAccActivity"] = "01";

		return $acctInfoData;
    }

    /**
	 * Obtiene transacciones realizadas
	 * @param int $id_customer codigo cliente
	 * @param int $valid completadas o no
	 * @param int $interval intervalo
	 * @return string $intervalType tipo de intervalo (DAY,MONTH)
	 **/
	private function numPurchaseCustomer($id_customer,$valid=1,$interval=1,$intervalType="day")
    {

        try {
            $from = new \DateTime("now");
            $from->modify('-' . $interval . ' ' . $intervalType);

            $from = $from->format('Y-m-d h:m:s');

            if ($valid==1) {
                $orderCollection = $this->_objectManager->get('Magento\Sales\Model\Order')->getCollection()
                    ->addFieldToFilter('customer_id', array('eq' => array($id_customer)))
                    ->addFieldToFilter('status', array(
                        'nin' => array('pending','cancel','canceled','refund'),
                        'notnull'=>true))
                    ->addAttributeToFilter('created_at', array('gt' => $from));
            } else {
                $orderCollection = $this->_objectManager->get('Magento\Sales\Model\Order')->getCollection()
                    ->addFieldToFilter('customer_id', array('eq' => array($id_customer)))
                    ->addFieldToFilter('status', array(
                        'notnull'=>true))
                    ->addAttributeToFilter('created_at', array('gt' => $from));

            }
            return $orderCollection->getSize();
        } catch (exception $e) {
            return 0;
        }
    }


    private function threeDSRequestorAuthenticationInfo()
    {

        $threeDSRequestorAuthenticationInfo = array();

        $logged = $this->_helper->customerIsLogged();
        $threeDSRequestorAuthenticationInfo["threeDSReqAuthMethod"] = ($logged)?"02":"01";

        if ($logged) {

            $lastVisited = new \DateTime($this->_session->getLoginAt());
            $threeDSReqAuthTimestamp = $lastVisited->format('Ymdhm');
		    $threeDSRequestorAuthenticationInfo["threeDSReqAuthTimestamp"] = $threeDSReqAuthTimestamp;
        }

		return $threeDSRequestorAuthenticationInfo;
    }


    /**
	 * Obtiene Fecha del primer envio a una direccion
	 * @param int $id_customer codigo cliente
	 * @param int $id_address_delivery direccion de envio
	 **/

	private function firstAddressDelivery($id_customer,$id_address_delivery)
    {

        try {

            $orderCollection = $this->_objectManager->get('Magento\Sales\Model\Order')->getCollection()
            ->addFieldToFilter('customer_id', array('eq' => $id_customer))
            ->getSelect()
            ->joinLeft('sales_order_address', "main_table.entity_id = sales_order_address.parent_id",array('customer_address_id'))
            ->where("sales_order_address.customer_address_id = $id_address_delivery ")
            ->limit('1')
            ->order('created_at ASC');

            $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();

            $results = $connection->fetchAll($orderCollection);

            if (sizeof($results)>0) {
                $firstOrder = current($results);
                return $firstOrder["created_at"];
            } else {
                return "";
            }
        } catch (exception $e) {
            return "";
        }
    }


    private function getShoppingCart($order)
    {

		$shoppingCartData = array();

        foreach ($order->getAllItems() as $key=>$item) {
            $shoppingCartData[$key]["sku"] = $item->getSku();
			$shoppingCartData[$key]["quantity"] = number_format($item->getQtyOrdered(), 0, '.', '');
			$shoppingCartData[$key]["unitPrice"] = number_format($item->getPrice()*100, 0, '.', '');
            $shoppingCartData[$key]["name"] = $item->getName();

            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());

            $cats = $product->getCategoryIds();

            $arrCat = array();
            foreach ($cats as $category_id) {
                $_cat = $this->_objectManager->create('Magento\Catalog\Model\Category')->load($category_id);
                $arrCat[] = $_cat->getName();
            }

			$shoppingCartData[$key]["category"] = implode("|",$arrCat);
         }

		return array("shoppingCart"=>array_values($shoppingCartData));
	}

    private function getMerchantData2($order){

        $name = $order->getCustomerFirstname();
        if (!isset($name) || empty($name)) {
            if ($order->getBillingAddress()) {
                $name = $order->getBillingAddress()->getFirstname();
            }
        }
        $lastName = $order->getCustomerLastname();
        if (!isset($lastName) || empty($lastName)) {
            if ($order->getBillingAddress()) {
                $lastName = $order->getBillingAddress()->getLastname();
            }
        }

        $Merchant_Data["scoring"]["customer"]["id"] = $order->getCustomerId();
        $Merchant_Data["scoring"]["customer"]["name"] = $name;
        $Merchant_Data["scoring"]["customer"]["surname"] = $lastName;
        $Merchant_Data["scoring"]["customer"]["email"] = $order->getCustomerEmail();

        $shipping = $order->getShippingAddress();

        if ($shipping){

            $Merchant_Data["scoring"]["customer"]["phone"] = $shipping->getTelephone();
            $Merchant_Data["scoring"]["customer"]["mobile"] = "";
            $Merchant_Data["scoring"]["customer"]["firstBuy"] = $this->_helper->getFirstOrder($order);

            $lastName = $shipping->getLastname();
            $lastName = isset($lastName) && !empty($lastName) ? ' '.$lastName : '';
            $city = $shipping->getCity();
            $state = $shipping->getRegionCode();
            $postalCode = $shipping->getPostcode();
            $country = $shipping->getCountryId();
            $phone = $shipping->getTelephone();
            $street = $shipping->getStreet();

            $Merchant_Data["scoring"]["shipping"]["address"]["streetAddress"] = isset($street[0]) ? $street[0] : self::NOT_AVAILABLE;
            $Merchant_Data["scoring"]["shipping"]["address"]["extraAddress"] = isset($street[1]) ? $street[1] : self::NOT_AVAILABLE;
            $Merchant_Data["scoring"]["shipping"]["address"]["city"] = isset($city) ? $city : self::NOT_AVAILABLE;
            $Merchant_Data["scoring"]["shipping"]["address"]["postalCode"] = isset($postalCode) ? $postalCode : self::NOT_AVAILABLE;
            $Merchant_Data["scoring"]["shipping"]["address"]["state"] = isset($state) ? $state : self::NOT_AVAILABLE;
            $Merchant_Data["scoring"]["shipping"]["address"]["country"] = isset($country) ? $country : self::NOT_AVAILABLE;

            // Time
            $Merchant_Data["scoring"]["shipping"]["time"] = "";
        }

        $billing = $order->getBillingAddress();
        $street = $billing->getStreet();

        $Merchant_Data["scoring"]["billing"]["address"]["streetAddress"] = isset($street[0]) ? $street[0] : self::NOT_AVAILABLE;
        $Merchant_Data["scoring"]["billing"]["address"]["extraAddress"] = isset($street[1]) ? $street[1] : self::NOT_AVAILABLE;
        $Merchant_Data["scoring"]["billing"]["address"]["city"] = $billing->getCity();
        $Merchant_Data["scoring"]["billing"]["address"]["postalCode"] = $billing->getPostcode();
        $Merchant_Data["scoring"]["billing"]["address"]["state"] = $billing->getRegionCode();
        $Merchant_Data["scoring"]["billing"]["address"]["country"] = $billing->getCountryId();

        $Merchant_Data["futureData"] = "";

        return urlencode(base64_encode(json_encode($Merchant_Data)));
    }




    /**
     * @desc Sets all the fields that is posted to Payment
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormPaycometUrl()
    {

        $order = $this->_session->getLastRealOrder();

        $merchant_code      = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass      = trim($this->_helper->getEncryptedConfigData('merchant_pass'));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key'));

        $payment_action = trim($this->_helper->getConfigData('payment_action'));

        $realOrderId = $order->getRealOrderId();
        //$fieldOrderId = $realOrderId.'_'.$timestamp;
        $fieldOrderId = $realOrderId;

        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);
        $customerId = $order->getCustomerId();

        $shopperLocale = $this->_resolver->getLocale();
        $language_data = explode("_",$shopperLocale);
        $language = $language_data[0];

        /** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId(null);
        $quoteRepository->save($quote);

        $Secure = ($this->isSecureTransaction($order,$amount))?1:0;

        $OPERATION = ($payment_action==PaymentAction::AUTHORIZE_CAPTURE)?1:3; // EXECUTE_PURCHASE : CREATE_PREAUTORIZATION

        $formFields = [];

        $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        $function_txt = "";

        // Payment/Preauthorization with Saved Card or jetIframe
        if ($order->getPaycometToken() != ""){
            $paycometToken = explode("|",$order->getPaycometToken());

            $IdUser = $paycometToken[0];
            $TokenUser = $paycometToken[1];

            $formFields['IDUSER'] = $IdUser;
            $formFields['TOKEN_USER'] = $TokenUser;

            if ($api_key != "") {

                $merchantData = $this->getMerchantData($order);

                try {
                    $apiRest = new ApiRest($api_key);
                    $response = $apiRest->form(
                        $OPERATION,
                        $language,
                        $merchant_terminal,
                        '',
                        [
                            'terminal' => $merchant_terminal,
                            'methods' => [1],
                            'order' => $fieldOrderId,
                            'amount' => $amount,
                            'currency' => $orderCurrencyCode,
                            'userInteraction' => 1,
                            'secure' => $Secure,
                            'idUser' => $IdUser,
                            'tokenUser' => $TokenUser,
                            'merchantData' => $merchantData,
                            'urlOk' => $this->getURLOK($order),
                            'urlKo' => $this->getURLKO($order)
                        ]
                    );

                    if ($response->errorCode==0) {
                        $response->URL_REDIRECT = $response->challengeUrl;
                    }
                    $response->DS_ERROR_ID = $response->errorCode;

                } catch (Exception $e) {
                    $response["url"] = "";
                    $response["error"]  = $response->errorCode;
                }

            } else {
                if ($OPERATION==1) {
                    $function_txt = "ExecutePurchaseTokenUrl";
                    $response = $ClientPaycomet->ExecutePurchaseTokenUrl($fieldOrderId, $amount, $orderCurrencyCode, $IdUser,$TokenUser, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), "");
                } else if ($OPERATION==3) {
                    $function_txt = "ExecutePreauthorizationTokenUrl";
                    $response = $ClientPaycomet->ExecutePreauthorizationTokenUrl($fieldOrderId, $amount, $orderCurrencyCode, $IdUser,$TokenUser, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), "");
                }
            }

        // Payment/Preautorization with New Card
        } else {

            if ($api_key != "") {

                $merchantData = $this->getMerchantData($order);

                try {
                    $apiRest = new ApiRest($api_key);
                    $response = $apiRest->form(
                        $OPERATION,
                        $language,
                        $merchant_terminal,
                        '',
                        [
                            'terminal' => $merchant_terminal,
                            'order' => $fieldOrderId,
                            'amount' => $amount,
                            'currency' => $orderCurrencyCode,
                            'userInteraction' => 1,
                            'secure' => $Secure,
                            'merchantData' => $merchantData,
                            'urlOk' => $this->getURLOK($order),
                            'urlKo' => $this->getURLKO($order)
                        ]
                    );
                    if ($response->errorCode==0) {
                        $response->URL_REDIRECT = $response->challengeUrl;
                    }
                    $response->DS_ERROR_ID = $response->errorCode;
                } catch (Exception $e) {
                    $response["url"] = "";
                    $response["error"]  = $response->errorCode;
                }

            } else {

                if ($OPERATION==1) {
                    $function_txt = "ExecutePurchaseUrl";
                    $response = $ClientPaycomet->ExecutePurchaseUrl($fieldOrderId, $amount, $orderCurrencyCode, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), "");
                } else if ($OPERATION==3) {
                    $function_txt = "CreatePreauthorizationUrl";
                    $response = $ClientPaycomet->CreatePreauthorizationUrl($fieldOrderId, $amount, $orderCurrencyCode, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), "");
                }
            }
        }

        $dataResponse = array();
        if ($response->DS_ERROR_ID==0) {
            $dataResponse["url"] = $response->URL_REDIRECT;
            $dataResponse["error"]  = 0;
        } else {
            $dataResponse["url"] = "";
            $dataResponse["error"]  = $response->DS_ERROR_ID;
            $this->_helper->logDebug("Error in " . $function_txt .": " . $response->DS_ERROR_ID);
        }

        return $dataResponse;
    }



    /**
     * Checkout getURLOK.
     *
     * @return string
     */
    private function getURLOK($order)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/process/result',$this->_buildSessionParams(true,$order)
        );
    }

    /**
     * Checkout getURLKO.
     *
     * @return string
     */
    private function getURLKO($order)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/process/result',$this->_buildSessionParams(false,$order)
        );
    }


    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     *
     * @return array
     */
    private function _buildSessionParams($result,$order){
        $result = ($result) ? '1' : '0';
        $timestamp = strftime('%Y%m%d%H%M%S');
        $merchant_code = $this->_helper->getConfigData('merchant_code');
        $orderid = $order->getRealOrderId();
        $sha1hash = $this->_helper->signFields("$timestamp.$merchant_code.$orderid.$result");

        return ['timestamp' => $timestamp, 'order_id' => $orderid, 'result' => $result, 'hash' => $sha1hash];
    }

}
