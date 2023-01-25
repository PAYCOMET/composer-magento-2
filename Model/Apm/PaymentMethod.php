<?php

namespace Paycomet\Payment\Model\Apm;

use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    /**
     * @var string
     */
    protected $_infoBlockType = \Paycomet\Payment\Block\Info\Info::class;

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
     * @param \Magento\Framework\App\RequestInterface                       $request
     * @param \Magento\Framework\UrlInterface                               $urlBuilder
     * @param \Paycomet\Payment\Helper\Data                                 $helper
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                   $resolver
     * @param \Magento\Framework\Model\Context                              $context
     * @param \Magento\Framework\Registry                                   $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory             $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                  $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                  $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface            $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                          $logger
     * @param \Paycomet\Payment\Logger\Logger                               $paycometLogger
     * @param \Magento\Framework\App\ProductMetadataInterface               $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                   $resourceInterface
     * @param \Magento\Checkout\Model\Session                               $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface             $customerRepository
     * @param \Magento\Framework\ObjectManagerInterface                     $objectmanager,
     * @param RemoteAddress                                                 $remoteAddress
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null  $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null            $resourceCollection
     * @param array                                                         $data
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
     * Check method for processing with base currency
     *
     * @return array currencies acepted
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
        $challengeUrl = "";
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation from PAYCOMET
        */
        $order->setCanSendNewEmailFlag(false);
        try {
            $executePurchaseResponse = $this->_helper->apmExecutePurchase($order, static::METHOD_ID);
            if ($executePurchaseResponse->errorCode==0 && $executePurchaseResponse->challengeUrl) {
                $challengeUrl = $executePurchaseResponse->challengeUrl;
            } else {
                $errorCode = $executePurchaseResponse->errorCode ?? 500;

                $this->_helper->logDebug('Order: ' . $order->getRealOrderId() . ': Error apmExecutePurchase ' . json_encode($executePurchaseResponse));
                throw new \Magento\Framework\Exception\LocalizedException(__("Error " . $errorCode), null, $errorCode);
            }

            // Se la asignamos para redirigir al final
            $payment->setAdditionalInformation("DS_CHALLENGE_URL", $challengeUrl);
            // Si tenemos methodData lo almacenamos
            if (isset($executePurchaseResponse->methodData)) {
                $payment->setAdditionalInformation("METHOD_DATA", json_encode($executePurchaseResponse->methodData));
            }
        } catch (\Exception $e) {
            $this->_helper->logDebug('Error apmExecutePurchase ' . $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__('Error: ' . $e->getCode()));
        }

        // Estado por defecto pedidos
        $order_status_default = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;

        // Obtenemos el estado para los nuevos pedidos del APM
        $order_status_apm = trim(
            (string)$this->_helper->getConfigData(
                'order_status',
                $order->getStoreId(),
                static::METHOD_CODE
            )
        );
        // Si no tiene uno asignado asignamos el valor por defecto, si no Magento asignara el seleccionado
        if ($order_status_apm == "") {
            $stateObject->setState($order_status_default);
            $stateObject->setStatus($order_status_default);
        }

        // Initialize order
        $stateObject->setIsNotified(false);

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
     * Accept Payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        // do nothing.
    }

    /**
     * Hold
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function hold(\Magento\Payment\Model\InfoInterface $payment)
    {
        // do nothing.
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
