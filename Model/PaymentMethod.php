<?php

namespace Paytpv\Payment\Model;

use Paytpv\Payment\Model\Config\Source\DMFields;
use Paytpv\Payment\Model\Config\Source\FraudMode;
use Paytpv\Payment\Model\Config\Source\PaymentAction;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Paytpv\Bankstore\Client;
use Paytpv\Payment\Observer\DataAssignObserver;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    const METHOD_CODE = 'paytpv_payment';
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
    protected $_infoBlockType = 'Paytpv\Payment\Block\Info\Info';

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
     * @var \Paytpv\Payment\Helper\Data
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
     * @var \Paytpv\Payment\Logger\Logger
     */
    private $_paytpvLogger;

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
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                      $request
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Paytpv\Payment\Helper\Data                              $helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                  $resolver
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Paytpv\Payment\Logger\Logger                            $paytpvLogger
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                  $resourceInterface
     * @param \Magento\Checkout\Model\Session                              $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Paytpv\Payment\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Paytpv\Payment\Logger\Logger $paytpvLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
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
        $this->_paytpvLogger = $paytpvLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
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
         * result confirmation from PAYTPV
         */
        $order->setCanSendNewEmailFlag(false);

        $orderCurrencyCode = $order->getBaseCurrencyCode();

        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);
    
        if ($amount <= 0) { 
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount'));
        }

        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();
        $currencyCode = $order->getBaseCurrencyCode();
        
        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');
        
        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYTPV_TOKENCARD);
        
        // Saved Card --> Get Data
        if (isset($hash) && $hash!=""){


            // Verifiy Token Data
            $data = $this->_helper->getTokenData($payment);
            $IdUser = $data["iduser"];
            $TokenUser = $data["tokenuser"];

            if (!isset($IdUser) || !isset($TokenUser)){
                throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
            }
     
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

        $amount_mage = $amount;
        
        $amount = $this->_helper->amountFromMagento($amount, $currencyCode);
        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');
        
        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        //CREATE_PREAUTHORIZATION
        $data = $this->_helper->getTokenData($payment);
        $IdUser = $data["iduser"];
        $TokenUser = $data["tokenuser"];

        $merchantdata = trim($this->_helper->getConfigData('merchantdata'));
        $merchant_data = null;
        if ($merchantdata)
            $merchant_data = $this->getMerchantData($order);

        $response = $ClientPaytpv->CreatePreauthorization($IdUser, $TokenUser, $amount, $realOrderId, $currencyCode,"","",null,$merchant_data,null);
        if (!isset($response) || !$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action failed'));
        }

        $response = (array) $response;
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

        $this->_helper->CreateTransInvoice($order,$response,$response["DS_MERCHANT_AUTHCODE"],$response["DS_MERCHANT_AMOUNT"]);

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

        $amount_mage = $amount;
        
        $amount = $this->_helper->amountFromMagento($amount, $currencyCode);
        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');
        
        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        $TransactionType = $payment->getAdditionalInformation('TransactionType');
        switch ($TransactionType){

            case 3: //PREAUTHORIZATION_CONFIRM
                $IdUser = $payment->getAdditionalInformation('IdUser');
                $TokenUser = $payment->getAdditionalInformation('TokenUser');


                $merchantdata = trim($this->_helper->getConfigData('merchantdata'));
                $merchant_data = null;
                if ($merchantdata)
                    $merchant_data = $this->getMerchantData($order);


                $response = $ClientPaytpv->PreauthorizationConfirm($IdUser, $TokenUser, $amount, $realOrderId);

                if (!isset($response) || !$response) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The capture action failed'));
                }
                
                $response = (array) $response;
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
                $IdUser = $data["iduser"];
                $TokenUser = $data["tokenuser"];

                if (!isset($IdUser) || !isset($TokenUser)){
                    throw new \Magento\Framework\Exception\LocalizedException(__('Token Card failed'));
                }  

                $merchantdata = trim($this->_helper->getConfigData('merchantdata'));
                $merchant_data = null;
                if ($merchantdata)
                    $merchant_data = $this->getMerchantData($order);

                $response = $ClientPaytpv->ExecutePurchase($IdUser,$TokenUser,$amount,$realOrderId,$currencyCode,"","",null,$merchant_data,null);

                if (!isset($response) || !$response) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The capture action failed'));
                }
                

                $response = (array) $response;
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
        $comments = $payment->getCreditMemo()->getComments();
        $grandTotal = $order->getBaseGrandTotal();
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($amount, $orderCurrencyCode);
        $IdUser = $payment->getAdditionalInformation('IdUser');
        $TokenUser = $payment->getAdditionalInformation('TokenUser');
        $AuthCode = $payment->getTransactionId();

        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');
        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        $AuthCode = str_replace("-refund","",$AuthCode);
      

        $response = $ClientPaytpv->ExecuteRefund($IdUser, $TokenUser, $realOrderId, $orderCurrencyCode, $AuthCode, $amount); 
        
        
        if (!isset($response) || !$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action failed'));
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
        $grandTotal = $order->getBaseGrandTotal();

        $orderCurrencyCode = $order->getBaseCurrencyCode();
        
        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);

        $IdUser = $payment->getAdditionalInformation('IdUser');
        $TokenUser = $payment->getAdditionalInformation('TokenUser');
        $AuthCode = $payment->getTransactionId();

        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');
        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");


        $response = $ClientPaytpv->PreauthorizationCancel($IdUser, $TokenUser, $amount, $realOrderId); 
        
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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

       
        $select = $connection->select()
            ->from(
                ['token' => 'paytpv_token'],
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
            'paytpv_payment/process/process',
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
       
        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYTPV_TOKENCARD);

        $payment_data_card = (isset($hash) && $hash!="")?$hash:0;      


        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->_helper->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);
        $secure_amount = $this->_helper->amountFromMagento($secure_amount, $orderCurrencyCode);

        // Transaccion Segura:
        // Si solo tiene Terminal Seguro
        if ($terminales==0){
            return true;   
        }
        // Si esta definido que el pago es 3d secure y no estamos usando una tarjeta tokenizada
        if ($secure_first && $payment_data_card===0){
            return true;
        }


        $total_amount = ($total_amount==0)?$amount:$total_amount;


        // Si se supera el importe maximo para compra segura
        if ($terminales==2 && ($secure_amount!="" && $secure_amount < $total_amount)){
            return true;
        }

        // Si esta definido como que la primera compra es Segura y es la primera compra aunque este tokenizada
        if ($terminales==2 && $secure_first && $payment_data_card!=0 && $this->_helper->isFirstPurchaseToken($order->getPayment()))
            return true;
        
        return false;
    }


    private function getMerchantData($order){

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
    public function getFormPaytpvUrl()
    {

        $order = $this->_session->getLastRealOrder();
        $paymentInfo = $order->getPayment();


        $merchant_code = trim($this->_helper->getConfigData('merchant_code'));
        $merchant_terminal = trim($this->_helper->getConfigData('merchant_terminal'));
        $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass');

        $merchant_terminales = trim($this->_helper->getConfigData('merchant_terminales'));
        $merchantdata = trim($this->_helper->getConfigData('merchantdata'));

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

        $baseUrl = $this->_storeManager->getStore($this->getStore())
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);


        $Secure = ($this->isSecureTransaction($order,$amount))?1:0;        



        $hash = $paymentInfo->getAdditionalInformation(DataAssignObserver::PAYTPV_TOKENCARD);

        $OPERATION = ($payment_action==PaymentAction::AUTHORIZE_CAPTURE)?1:3; // EXECUTE_PURCHASE : CREATE_PREAUTORIZATION

        $formFields = [];

        $ClientPaytpv = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");

        $merchantData = null;
        if ($merchantdata)
            $merchantData = $this->getMerchantData($order);

        // Payment/Preauthorization with Saved Card
        if (isset($hash) && $hash!=""){

            $data = $this->_helper->getTokenData($paymentInfo);
            $IdUser = $data["iduser"];
            $TokenUser = $data["tokenuser"];

            $formFields['IDUSER'] = $IdUser;
            $formFields['TOKEN_USER'] = $TokenUser;

            if ($OPERATION==1){
                $response = $ClientPaytpv->ExecutePurchaseTokenUrl($fieldOrderId, $amount, $orderCurrencyCode, $IdUser,$TokenUser, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), $merchantData);
            }else if ($OPERATION==3){
                $response = $ClientPaytpv->ExecutePreauthorizationTokenUrl($fieldOrderId, $amount, $orderCurrencyCode, $IdUser,$TokenUser, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), $merchantData);
            }

        // Payment/Preautorization with New Card
        }else{
            if ($OPERATION==1){
                $response = $ClientPaytpv->ExecutePurchaseUrl($fieldOrderId, $amount, $orderCurrencyCode, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), $merchantData);
            }else if ($OPERATION==3){
                $response = $ClientPaytpv->CreatePreauthorizationUrl($fieldOrderId, $amount, $orderCurrencyCode, $language, "", $Secure, null, $this->getURLOK($order), $this->getURLKO($order), $merchantData);
            }
        }
        
        if ($response->DS_ERROR_ID==0){
            $url = $response->URL_REDIRECT;
        }

        return $url;
    }



    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    private function getURLOK($order)
    {
        return $this->_urlBuilder->getUrl(
            'paytpv_payment/process/result',$this->_buildSessionParams(true,$order)
        );
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    private function getURLKO($order)
    {
        return $this->_urlBuilder->getUrl(
            'paytpv_payment/process/result',$this->_buildSessionParams(false,$order)
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
