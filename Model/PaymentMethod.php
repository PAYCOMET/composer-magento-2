<?php

namespace Paycomet\Payment\Model;

use Paycomet\Payment\Model\Config\Source\PaymentAction;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Paycomet\Bankstore\ApiRest;
use Paycomet\Payment\Observer\DataAssignObserver;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    public const METHOD_ID = 1;
    public const METHOD_CODE = 'paycomet_payment';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = \Paycomet\Payment\Block\Info\Info::class;

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

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var RemoteAddress
     */
    private $_remoteAddress;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                      $request
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Paycomet\Payment\Helper\Data                                $helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                  $resolver
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Paycomet\Payment\Logger\Logger                              $paycometLogger
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                  $resourceInterface
     * @param \Magento\Checkout\Model\Session                              $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Framework\ObjectManagerInterface                    $objectmanager,
     * @param RemoteAddress                                                $remoteAddress
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
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
     * Get Accepted Currency Codes
     */
    public function getAcceptedCurrencyCodes()
    {
        return [$this->getConfigData('currency')];
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->getAcceptedCurrencyCodes())) {
            return false;
        }
        return true;
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
     * Initialize
     *
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
        if (isset($hash) && $hash!="") {

            // Verifiy Token Data
            $data = $this->_helper->getTokenData($payment);
            if (!isset($data["iduser"]) || !isset($data["tokenuser"])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
            }
            $idUser = $data["iduser"];
            $tokenUser = $data["tokenuser"];
            $tokenCardPayment = true;

        // Fin Pago mediante tarjeta tokenizada ------------------------------------------------------
        } if (isset($jetToken) && $jetToken!="") {

            $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal'));
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
                    $response = [];
                    $response["DS_RESPONSE"] = ($tokenCard->errorCode > 0)? 0 : 1;

                    if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                    }

                    if ($response["DS_RESPONSE"]==1) {
                        $response["DS_IDUSER"] = $tokenCard->idUser ?? 0;
                        $response["DS_TOKEN_USER"] = $tokenCard->tokenUser ?? '';
                    }

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
                }
            } else {
                $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
            }

            $idUser = $response["DS_IDUSER"];
            $tokenUser = $response["DS_TOKEN_USER"];

            $tokenCardPayment = true;

        }

        // Fin Pago mediante jetIFrame -------------------------------------------------------------------

        // If a Payment with Saved Card or JetIframe
        if ($tokenCardPayment) {

            // Si es pago con Token o jetIframe guardamos los datos del token en el pedido
            $order->setPaycometToken($idUser."|".$tokenUser);
            $order->save();
        }

        // New Card or Token Card with 3D SecurePayment
        // Initialize order to PENDING_PAYMENT
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * Send authorize request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
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
        if (!isset($data["iduser"]) || !isset($data["tokenuser"])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
        }
        $IdUser = $data["iduser"];
        $TokenUser = $data["tokenuser"];

        $storeId = $order->getStoreId();
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal', $storeId));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key', $storeId));

        $methodId = 1;
        $secure = 0;
        $userInteraction = 1;
        $defered = 0;

        // Uso de Rest
        if ($api_key != "") {
            $merchantData = $this->_helper->getMerchantData($order, self::METHOD_ID);
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
                    $this->_helper->getURLOK($order),
                    $this->_helper->getURLKO($order),
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
                $response = [];
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

            } catch (\Exception $e) {
                $response["DS_RESPONSE"] = 0;
                $response["DS_ERROR_ID"] = $createPreauthorizationResponse->errorCode;
            }

        } else {
            $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
        }

        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    sprintf(
                        'Payment failed. Error ( %s ) - %s',
                        $response['DS_ERROR_ID'],
                        $this->_helper->getErrorDesc($response['DS_ERROR_ID'])
                    )
                )
            );
        }

        // Add Extra Data to Response
        $errorDesc = $this->_helper->getErrorDesc($response['DS_ERROR_ID']);

        $response["ErrorDescription"] = (string)$errorDesc;
        $response["SecurePayment"] = 0;

        // Set Operation Type
        $response["TransactionType"] = 3;

        $this->_helper->createTransInvoice($order, $response);

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

        $storeId = $order->getStoreId();
        $merchant_terminal  = trim($this->_helper->getConfigData('merchant_terminal', $storeId));
        $api_key            = trim($this->_helper->getEncryptedConfigData('api_key', $storeId));

        $TransactionType = $payment->getAdditionalInformation('TransactionType');
        switch ($TransactionType) {

            case 3: //PREAUTHORIZATION_CONFIRM
                // Uso de Rest
                if ($api_key != "") {
                    try {
                        $apiRest = new ApiRest($api_key);

                        $AuthCode = str_replace("-capture", "", $payment->getTransactionId());

                        $confirmPreautorization = $apiRest->confirmPreautorization(
                            $realOrderId,
                            $merchant_terminal,
                            $amount,
                            $this->_remoteAddress->getRemoteAddress(),
                            $AuthCode,
                            '0'
                        );

                        $response = [];
                        $response["DS_RESPONSE"] = ($confirmPreautorization->errorCode > 0)? 0 : 1;
                        $response["DS_ERROR_ID"] = $confirmPreautorization->errorCode;

                        if ($response["DS_RESPONSE"]==1) {
                            $response["DS_MERCHANT_AUTHCODE"] = $confirmPreautorization->authCode;
                            $response["DS_MERCHANT_AMOUNT"] = $confirmPreautorization->amount;
                        }

                    } catch (\Exception $e) {
                        $response["DS_RESPONSE"] = 0;
                        $response["DS_ERROR_ID"] = $confirmPreautorization->errorCode;
                    }

                } else {
                    $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
                }

                if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            sprintf(
                                'Payment failed. Error ( %s ) - %s',
                                $response['DS_ERROR_ID'],
                                $this->_helper->getErrorDesc($response['DS_ERROR_ID'])
                            )
                        )
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
                if (!isset($data["iduser"]) || !isset($data["tokenuser"])) {
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
                    $merchantData = $this->_helper->getMerchantData($order, self::METHOD_ID);
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
                            $this->_helper->getURLOK($order),
                            $this->_helper->getURLKO($order),
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

                        $response = [];

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
                    } catch (\Exception $e) {
                        $response["DS_RESPONSE"] = 0;
                        $response["DS_ERROR_ID"] = $executePurchaseResponse->errorCode;
                    }

                } else {
                    $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
                }

                if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {

                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            sprintf(
                                'Payment failed. Error ( %s ) - %s',
                                $response['DS_ERROR_ID'],
                                $this->_helper->getErrorDesc($response['DS_ERROR_ID'])
                            )
                        )
                    );
                }

                // Add Extra Data to Response
                $errorDesc = $this->_helper->getErrorDesc($response['DS_ERROR_ID']);
                $response["ErrorDescription"] = (string)$errorDesc;
                $response["SecurePayment"] = 0;

                $this->_helper->createTransInvoice($order, $response);

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
        $this->_helper->refund($payment, $amount);
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
        $this->_helper->void($payment);
        return $this;
    }

    /**
     * Accept payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Accept Payment
    }

    /**
     * Hold
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function hold(\Magento\Payment\Model\InfoInterface $payment)
    {
        // Hold
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

    /**
     * Get checkout Cards
     */
    public function getCheckoutCards()
    {
        $data = [];
        // Si no esta logado no cargamos nada
        if (!$this->_helper->customerIsLogged()) {
            return $data;
        }

        $resource = $this->_objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(
                ['token' => $resource->getTableName('paycomet_token')],
                ['customer_id', 'iduser', 'tokenuser', 'hash', 'cc', 'brand' , 'expiry' , 'desc']
            )
            ->where('customer_id = ?', $this->_helper->getCustomerId())
            ->order('date DESC');
        $data = $connection->fetchAll($select);

        $data =  $this->_helper->validateTokenInfo($data);

        // Return only valid cards
        return $data["valid"];
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

    /**
     * Get Form Paycomet Url
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormPaycometUrl()
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

        $formFields = [];

        $function_txt = "";

        $dataResponse = [];

        if ($api_key != "") {

            $merchantData = $this->_helper->getMerchantData($order, self::METHOD_ID);
            
            $paymentData = [
                'terminal' => $merchant_terminal,
                'methods' => [1],
                'order' => $fieldOrderId,
                'amount' => $amount,
                'currency' => $orderCurrencyCode,
                'userInteraction' => 1,
                'secure' => $Secure,
                'merchantData' => $merchantData,
                'urlOk' => $this->_helper->getURLOK($order),
                'urlKo' => $this->_helper->getURLKO($order)
            ];

            // Payment/Preauthorization with Saved Card or jetIframe add idUser/tokenUser to paymentData
            if ($order->getPaycometToken() != "") {
                $paycometToken = explode("|", $order->getPaycometToken());

                $IdUser = $paycometToken[0];
                $TokenUser = $paycometToken[1];

                $formFields['IDUSER'] = $IdUser;
                $formFields['TOKEN_USER'] = $TokenUser;

                $paymentData['idUser'] = $IdUser;
                $paymentData['tokenUser'] = $TokenUser;
            }

            try {
                $apiRest = new ApiRest($api_key);
                $response = $apiRest->form(
                    $OPERATION,
                    $language,
                    $merchant_terminal,
                    '',
                    $paymentData
                );

                if ($response->errorCode==0) {
                    $response->URL_REDIRECT = $response->challengeUrl;
                }
                $response->DS_ERROR_ID = $response->errorCode;

            } catch (\Exception $e) {
                $response["url"] = "";
                $response["error"]  = $response->errorCode;
            }
        } else {
            $this->_helper->logDebug(__("ERROR: PAYCOMET API KEY required"));
            $dataResponse["url"] = "";
            $dataResponse["error"]  = 1004;
            return $dataResponse;
        }

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
}
