<?php

namespace Paycomet\Payment\Model\Apm\Ideal;

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
    const METHOD_ID = 12;
    const METHOD_CODE = 'paycomet_ideal';
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
    protected $_canAuthorize = false;

    /**
     * @var bool
     */
    protected $_canCapture = false;

    /**
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * @var bool
     */
    protected $_canCaptureOnce = false;

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
                
        // Obtenemos la challenge del APM
        $challengUrl = $this->_helper->getAPMPaycometUrl($order, self::METHOD_ID);
    
        // Se la asignamos para redirigir al final        
        $payment->setAdditionalInformation("DS_CHALLENGE_URL", $challengUrl);

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
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount){ }

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
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) { }

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


    
}
