<?php

namespace Paycomet\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Paycomet\Payment\Model\Config\Source\Environment;
use Paycomet\Payment\Observer\DataAssignObserver;
use Paycomet\Payment\Model\Config\Source\PaymentAction;
use Paycomet\Bankstore\Client;
use Paycomet\Bankstore\ApiRest;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const METHOD_CODE = 'paycomet_payment';
    const CUSTOMER_ID = 'customer';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $_encryptor;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    private $_country;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $_moduleList;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $_quoteRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    private $_paycometLogger;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    private $_resourceInterface;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $_resolver;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    private $_orderHistoryFactory;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $_orderSender;

    /** @var  CustomerInterfaceFactory */
    private $_customerFactory;


    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context             $context
     * @param \Magento\Framework\Encryption\EncryptorInterface  $encryptor
     * @param \Magento\Directory\Model\Config\Source\Country    $country
     * @param \Magento\Quote\Api\CartRepositoryInterface        $quoteRepository
     * @param \Magento\Framework\Module\ModuleListInterface     $moduleList
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface   $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface       $resourceInterface
     * @param \Magento\Framework\Locale\ResolverInterface       $resolver
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface       $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder      $searchCriteriaBuilder
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender             $orderSender
     * @param \Magento\Customer\Model\Session                   $session
     * @param \Magento\Customer\Model\CustomerFactory           $customerFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Paycomet\Payment\Logger\Logger $paycometLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Paycomet\Payment\Logger\Logger $logger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Customer\Model\Session $session,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->_country = $country;
        $this->_moduleList = $moduleList;
        $this->_quoteRepository = $quoteRepository;
        $this->_storeManager = $storeManager;
        $this->_paycometLogger = $paycometLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_resolver = $resolver;
        $this->_customerRepository = $customerRepository;
        $this->_session = $session;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;
        $this->_customerFactory = $customerFactory;
    }

    /**
     * @desc Sign fields
     *
     * @return string
     */
    public function signFields($fields, $account = null)
    {
        //do we need to use a specific config
        if (!isset($account)) {
            $account = 'merchant_pass';
        }
        $secret = $this->getEncryptedConfigData($account);
        $sha1hash = sha1($fields);
        $tmp = "$sha1hash.$secret";

        return sha1($tmp);
    }


    

    /**
     * @desc Check if configuration is set to sandbox mode
     *
     * @return bool
     */
    public function isSandboxMode()
    {
        return $this->getConfigData('environment') == Environment::ENVIRONMENT_SANDBOX;
    }

    /**
     * @desc Get payment form url
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->getConfigData('payment_url');
    }

    /**
     * @desc Get remote api url
     *
     * @return string
     */
    public function getRemoteApiUrl()
    {
        return $this->getConfigData('api_url');
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    private function getURLOK($orderid)
    {
        
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/view',$this->_buildSessionParams(true,$orderid)
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
    private function getURLKO($orderid)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/view',$this->_buildSessionParams(false,$orderid)
        );
    }


    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     *
     * @return array
     */
    public function _buildSessionParams($result,$orderid)
    {
        $result = ($result) ? '1' : '0';
        $timestamp = strftime('%Y%m%d%H%M%S');
        $merchant_code = $this->getConfigData('merchant_code');
        $sha1hash = $this->signFields("$timestamp.$merchant_code.$orderid.$result");

        return ['timestamp' => $timestamp, 'order_id' => $orderid, 'result' => $result, 'hash' => $sha1hash];
    }


    /**
     * 
     * @return string paycomet adduser url
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaycometAddUserUrl()
    {
        if (!$this->_session->isLoggedIn()) {
            return [];
        }

        
        $merchant_code      = trim($this->getConfigData('merchant_code'));
        $merchant_terminal  = trim($this->getConfigData('merchant_terminal'));
        $merchant_pass      = trim($this->getEncryptedConfigData('merchant_pass'));
        $api_key            = trim($this->getEncryptedConfigData('api_key'));

       
        $fieldOrderId = $this->_session->getCustomer()->getId() . "_" . $this->_storeManager->getStore()->getId(); //UserId | StoreId

        $shopperLocale = $this->_resolver->getLocale();
        $language_data = explode("_",$shopperLocale);
        $language = $language_data[0];

        $dataResponse = array();

        // Uso de Rest
        if ($api_key != "") {
            try {

                $dataResponse["url"]  = ""; // Inicializamos

                $apiRest = new ApiRest($api_key);
                $formResponse = $apiRest->form(
                    107,
                    $language,
                    $merchant_terminal,
                    '',
                    [
                        'terminal' => (int) $merchant_terminal,
                        'order' => (string) $fieldOrderId,
                        'urlOk' => (string) $this->getURLOK($fieldOrderId),
                        'urlKo' => (string) $this->getURLKO($fieldOrderId)
                    ]
                );
                
                
                $dataResponse["error"]  = $formResponse->errorCode;
                if ($formResponse->errorCode == 0) {
                    $dataResponse["url"] = $formResponse->challengeUrl;                    
                }                

            } catch (Exception $e) {
               
                $dataResponse["error"]  = $formResponse->errorCode;
                $this->logDebug("Error in Rest 107: " . $e->getMessage());
            }
        
        } else {
            $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");
            $response = $ClientPaycomet->AddUserUrl($fieldOrderId, $language, $this->getURLOK($fieldOrderId), $this->getURLKO($fieldOrderId));

            $function_txt = "AddUserUrl";
        
            if ($response->DS_ERROR_ID==0) {
                $dataResponse["url"] = $response->URL_REDIRECT;
                $dataResponse["error"]  = 0;
            } else {
                $dataResponse["url"] = $response->URL_REDIRECT;
                $dataResponse["error"]  = $response->DS_ERROR_ID;
                $this->logDebug("Error in " . $function_txt .": " . $response->DS_ERROR_ID . "; URL: " . $response->URL_REDIRECT);
            }
        }
               
        return $dataResponse;

    }


    public function customerIsLogged()
    {
        return $this->_session->isLoggedIn();
    }

   
    /**
     * @desc Logs debug information if enabled
     *
     * @param mixed
     */
    public function logDebug($message)
    {
        if ($this->getConfigData('debug_log') == '1') {
            $this->_paycometLogger->debug($message);
        }
    }

    /**
     * @desc Cancels the order
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    public function cancelOrder($order)
    {
        $orderStatus = $this->getConfigData('payment_cancelled');
        $order->setActionFlag($orderStatus, true);
        $order->cancel()->save();
    }

    /**
     * @desc Load a quote based on id
     *
     * @param $quoteId
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote($quoteId)
    {
        // get quote from quoteId
        $quote = $this->_quoteRepository->get($quoteId);

        return $quote;
    }

    /**
     * @desc Removes the response fields that we don't want stored
     *
     * @param array $response
     *
     * @return array
     */
    public function stripFields($response)
    {
        if ($this->isSandboxMode()) {
            return $response;
        }
        $returnedFields = [];
        $excludedFields = [];

        foreach ($response as $key => $field) {
            if (!in_array(strtoupper($key), $excludedFields)) {
                $returnedFields[$key] = $field;
            }
        }
        

        return $returnedFields;
    }

    /**
     * @desc Trims the response card digits field to only contain the last 4
     *
     * @param array $response
     *
     * @return array
     */
    public function trimCardDigits($response)
    {
        if (isset($response['CARDDIGITS']) && strlen($response['CARDDIGITS']) > 4) {
            $response['CARDDIGITS'] = substr($response['CARDDIGITS'], -4);
        }
        if (isset($response['SAVED_PMT_DIGITS']) && strlen($response['SAVED_PMT_DIGITS']) > 4) {
            $response['SAVED_PMT_DIGITS'] = substr($response['SAVED_PMT_DIGITS'], -4);
        }

        return $response;
    }

    /**
     * @desc Strips and trims the response and returns a new array of fields
     *
     * @param array $response
     *
     * @return array
     */
    public function stripTrimFields($response)
    {
        $fields = $this->stripFields($response);

        return $this->trimCardDigits($fields);
    }

    /**
     * @desc Strips and trims the xml and returns the new xml
     *
     * @param string $xml
     *
     * @return string
     */
    public function stripXML($xml)
    {
        $patterns = ['/(<sha1hash>).+(<\/sha1hash>)/',
                      '/(<md5hash>).+(<\/md5hash>)/',
                      '/(<refundhash>).+(<\/refundhash>)/', ];

        return preg_replace($patterns, '', $xml);
    }

    /**
     * @desc Converts the magento decimal amount into a int one used by Paycomet
     *
     * @param float  $amount
     * @param string $currencyCode
     *
     * @return int
     */
    public function amountFromMagento($amount, $currencyCode)
    {
        $minor = $this->_getCurrencyMinorUnit($currencyCode);

        return round($amount * $minor);
    }

    /**
     * @desc Converts the paycomet int amount into a decimal one used by Paycomet
     *
     * @param string $amount
     * @param string $currencyCode
     *
     * @return float
     */
    public function amountFromPaycomet($amount, $currencyCode)
    {
        $minor = $this->_getCurrencyMinorUnit($currencyCode);

        return floatval($amount) / $minor;
    }

    /**
     * @desc Gets the amount of currency minor units. This would be used to divide or
     * multiply with. eg. cents with 2 minor units would mean 10^2 = 100
     *
     * @param string $currencyCode
     *
     * @return int
     */
    private function _getCurrencyMinorUnit($currencyCode)
    {
        if ($this->checkForFirstMinorUnit($currencyCode)) {
            return 1;
        }
        switch ($currencyCode) {
            case 'BHD':
            case 'IQD':
            case 'JOD':
            case 'KWD':
            case 'LYD':
            case 'OMR':
            case 'TND':
                return 1000;
            case 'CLF':
                return 10000;
        }

        return 100;
    }

    private function checkForFirstMinorUnit($currencyCode)
    {
        return in_array($currencyCode, ['BYR', 'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'KMF','KRW', 'PYG', 'RWF', 'UGX', 'UYI', 'VUV', 'VND', 'XAF', 'XOF', 'XPF', ]);
    }

    /**
     * @desc Sets additional information fields on the payment class
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param array                              $response
     */
    public function setAdditionalInfo($payment, $response)
    {
        $fields = $this->stripFields($response);
        foreach ($fields as $key => $value) {
            $payment->setAdditionalInformation($key, $value);
        }
    }

    /**
     * @desc Gives back configuration values
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfig($field, self::METHOD_CODE, $storeId);
    }

    /**
     * @desc Gives back configuration values as flag
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfig($field, self::METHOD_CODE, $storeId, true);
    }

    /**
     * @desc Gives back encrypted configuration values
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEncryptedConfigData($field, $storeId = null)
    {
        return $this->_encryptor->decrypt(trim($this->getConfigData($field, $storeId)));
    }

    /**
     * @desc Retrieve information from payment configuration
     *
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     *
     * @return bool|mixed
     */
    public function getConfig($field, $paymentMethodCode, $storeId, $flag = false)
    {
        $path = 'payment/'.$paymentMethodCode.'/'.$field;
        if (null === $storeId) {
            $storeId = $this->_storeManager->getStore()->getId();
        }

        if (!$flag) {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->scopeConfig->isSetFlag($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
    }

    public function getCustomerId(){
        return $this->_session->getCustomer()->getId();
    }

    public function getCustomerById($id) {
        return $this->_customerFactory->create()->load($id);
    }
    
    public function createTransaction($type, $transactionid, $order = null, $paymentData = array())
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($transactionid);
            $payment->setTransactionId($transactionid);

            //Set information
            $this->setAdditionalInfo($payment, $paymentData);


            switch ($type) {
                case \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE:
                    $message = __('Captured amount of %1',$order->getBaseCurrency()->formatTxt($order->getGrandTotal()));
                break;

                case \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH:
                    $message = __('Authorize amount of %1',$order->getBaseCurrency()->formatTxt($order->getGrandTotal()));
                break;
            }            
            
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                                ->setOrder($order)
                                ->setTransactionId($transactionid)
                                ->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData])
                                ->setFailSafe(true)
                                ->build($type);


            if ($type==\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH)
                $transaction->setIsClosed(false);
             
            $order->setPayment($payment);

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                  ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

            $order->save();

            $this->_addHistoryComment($order, $message);

            return $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Create Transaction error'));
        }
    }


    /**
     * @desc Create an invoice
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $pasref
     * @param string                    $amount
     */
    public function createInvoice($order, $pasref, $amount)
    {
        $invoice = $order->prepareInvoice();
        $invoice->getOrder()->setIsInProcess(true);

        // set transaction id so you can do a online refund from credit memo
        $invoice->setTransactionId($pasref);
        $invoice->register()
                ->pay()
                ->save();

       
        $message = __(
            'Invoiced amount of %1 Transaction ID: %2',
            $order->getBaseCurrency()->formatTxt($amount),
            $pasref
        );
        $this->_addHistoryComment($order, $message);
        
    }


    /**
     * @desc Add a comment to order history
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $message
     */
    private function _addHistoryComment($order, $message)
    {
        $history = $this->_orderHistoryFactory->create()
          ->setStatus($order->getStatus())
          ->setComment($message)
          ->setEntityName('order')
          ->setOrder($order);

        $history->save();
    }


    public function getTokenData($payment)
    {        
        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_TOKENCARD);
        $customer_id = $this->getCustomerId();
       
        if ($hash=="" || $customer_id=="") {
            return null;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $conds[] = $connection->quoteInto("hash" . ' = ?', $hash);
        $conds[] = $connection->quoteInto("customer_id" . ' = ?', $customer_id);
        $where = implode(' AND ', $conds);
       
        $select = $connection->select()
            ->from(
                ['token' => 'paycomet_token'],
                ['iduser', 'tokenuser']
            )
            ->where($where);
        $data = $connection->fetchRow($select);

        return $data;

    }

    /*
    @@ TODO
    */
    public function getFirstOrder($order)
    {
        
        $searchCriteria = $this->_searchCriteriaBuilder
        ->addFilter('customer_id', $this->getCustomerId())
        ->addFilter('status', array('pending','cancel','canceled','refund'), 'nin')
        ->create();  

        $orders = $this->_orderRepository->getList($searchCriteria);

        if (sizeof($orders)>0) {
            return 0;
        }
        return 1;
    }

    public function isFirstPurchaseToken($payment)
    {

        $data = $this->getTokenData($payment);
        
        if (isset($data['iduser']) && isset($data['tokenuser'])) {            
            $paycomet_token = $data['iduser'] . "|" . $data['tokenuser'];

            $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('customer_id', $this->getCustomerId())
            ->addFilter('paycomet_token', $paycomet_token)
            ->addFilter('status', array('pending','cancel','canceled','refund'), 'nin')
            ->create();  

            $orders = $this->_orderRepository->getList($searchCriteria);

            if (sizeof($orders)>0) {
                return false;
            }
        }
        return true;
    }


    public function CreateTransInvoice($order,$response)
    {

        $payment = $order->getPayment();

        // Gateway Response
        if (isset($response['AuthCode'])) {
            $transactionid = $response['AuthCode'];
            $amount = $response['Amount'];
        // Webservice Response
        } else {
            $transactionid = $response['DS_MERCHANT_AUTHCODE'];
            $amount = $response['DS_MERCHANT_AMOUNT'];
        }
        $amount = $this->amountFromPaycomet($amount, $order->getBaseCurrencyCode());        
        
        $payment_action = $this->getConfigData('payment_action', $order->getStoreId());
        $isAutoSettle = $payment_action == PaymentAction::AUTHORIZE_CAPTURE;

        $type = $isAutoSettle
              ? \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE
              : \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;

        
        //Set information
        //$this->setAdditionalInfo($payment, $response);
       
        //Add order Transaction        
        $this->createTransaction($type, $transactionid, $order, $response);        
        //Should we invoice
        if ($isAutoSettle) {
            $this->createInvoice($order, $transactionid, $amount);
        }        
        //Send order email
        if (!$order->getEmailSent()) {
            $this->_orderSender->send($order);
        }        

        // Set PAYCOMET iduser|tokenuser to order
        $IdUser = 0; $TokenUser = ""; // Inicializamos
        if (isset($response['IdUser']) && isset($response['TokenUser']) ) {
            $IdUser = $response['IdUser'];
            $TokenUser = $response['TokenUser'];            
        } else {            
            $data = $this->getTokenData($payment);
            if (isset($data['iduser']) && isset($data['tokenuser'])) {
                $IdUser = $data["iduser"];
                $TokenUser = $data["tokenuser"];
            }
        }
        // Si tenemos token se lo asociadmos al pedido
        if ($IdUser>0 && $TokenUser!="") {
            $order->setPaycometToken($IdUser."|".$TokenUser);
        }
                        
        $order->save();
        
        // Save Customer Card Token for future purchase
        $savecard = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_SAVECARD);
        $token = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_TOKENCARD);
        if ($savecard && $token=="") {
            $customerId = $order->getCustomerId();
            if (!empty($customerId)) {
                $this->_handleCardStorage($response, $customerId, $order->getStoreId());
            }
        }      

    }


    /**
     * @desc Handles the card storage fields
     *
     * @param array  $response
     * @param string $customerId
     */
    public function _handleCardStorage($response, $customerId, $storeId = null)
    {
        try {

            $IdUser = $response['IdUser'];
            $TokenUser = $response['TokenUser'];

            $merchant_code      = trim($this->getConfigData('merchant_code',$storeId));
            $merchant_terminal  = trim($this->getConfigData('merchant_terminal',$storeId));
            $merchant_pass      = trim($this->getEncryptedConfigData('merchant_pass',$storeId));
            $api_key            = trim($this->getEncryptedConfigData('api_key',$storeId));
            
            if ($api_key != "") {
                        
                $apiRest = new ApiRest($api_key);
                $formResponse = $apiRest->infoUser(
                    $IdUser,
                    $TokenUser,
                    $merchant_terminal
                );

                $resp = array();
                $resp["DS_MERCHANT_PAN"] = $formResponse->pan;
                $resp["DS_CARD_BRAND"] = $formResponse->cardBrand;
                $resp["DS_EXPIRYDATE"] = $formResponse->expiryDate;
                $resp["DS_ERROR_ID"] = 0;
        
            } else {
                $ClientPaycomet = new Client($merchant_code,$merchant_terminal,$merchant_pass,"");
                $resp = $ClientPaycomet->InfoUser($IdUser, $TokenUser);
                $resp = (array)$resp;
            }
            
            if ('' == $resp['DS_ERROR_ID'] || 0 == $resp['DS_ERROR_ID']) {
                return $this->addCustomerCard($customerId,$IdUser,$TokenUser,$resp);
            } else{
                return false;
            }
                    
        } catch (\Exception $e) {
            //card storage exceptions should not stop a transaction
            $this->_logger->critical($e);
        }
    }


    /**
     * @desc Manage cards that were edited while the user was on payment
     *
     * @param string $cards
     */
    private function addCustomerCard($customerId,$IdUser,$TokenUser,$response)
    {
        try{
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();

            $card =  $response["DS_MERCHANT_PAN"];
            $card = 'XXXX-XXXX-XXXX-' . substr($card, -4);
            $card_brand =  $response["DS_CARD_BRAND"];
            $expiryDate = $response["DS_EXPIRYDATE"];

            $hash = hash('sha256', $IdUser . $TokenUser);

            $connection->insert(
                $resource->getTableName('paycomet_token'),
                ['customer_id' => $customerId, 'hash' => $hash, 'iduser' => $IdUser, 'tokenuser' => $TokenUser, 'cc' => $card , 'brand' => $card_brand, 'expiry' => $expiryDate, 'date' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)]
            );
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }

    }
    



    public function getErrorDesc($code)
    {
        switch ($code){
            case 0: return __("No error"); break;
            case 1: return __("Error"); break;
            case 100: return __("Expired credit card"); break;
            case 101: return __("Credit card blacklisted"); break;
            case 102: return __("Operation not allowed for the credit card type"); break;
            case 103: return __("Please, call the credit card issuer"); break;
            case 104: return __("Unexpected error"); break;
            case 105: return __("Insufficient funds"); break;
            case 106: return __("Credit card not registered or not logged by the issuer"); break;
            case 107: return __("Data error. Validation Code"); break;
            case 108: return __("PAN Check Error"); break;
            case 109: return __("Expiry date error"); break;
            case 110: return __("Data error"); break;
            case 111: return __("CVC2 block incorrect"); break;
            case 112: return __("Please, call the credit card issuer"); break;
            case 113: return __("Credit card not valid"); break;
            case 114: return __("The credit card has credit restrictions"); break;
            case 115: return __("Card issuer could not validate card owner"); break;
            case 116: return __("Payment not allowed in off-line authorization"); break;
            case 118: return __("Expired credit card. Please capture card"); break;
            case 119: return __("Credit card blacklisted. Please capture card"); break;
            case 120: return __("Credit card lost or stolen. Please capture card"); break;
            case 121: return __("Error in CVC2. Please capture card"); break;
            case 122: return __("Error en Pre-Transaction process. Try again later."); break;
            case 123: return __("Operation denied. Please capture card"); break;
            case 124: return __("Closing with agreement"); break;
            case 125: return __("Closing without agreement"); break;
            case 126: return __("Cannot close right now"); break;
            case 127: return __("Invalid parameter"); break;
            case 128: return __("Transactions were not accomplished"); break;
            case 129: return __("Duplicated internal reference"); break;
            case 130: return __("Original operation not found. Could not refund"); break;
            case 131: return __("Expired preauthorization"); break;
            case 132: return __("Operation not valid with selected currency"); break;
            case 133: return __("Error in message format"); break;
            case 134: return __("Message not recognized by the system"); break;
            case 135: return __("CVC2 block incorrect"); break;
            case 137: return __("Credit card not valid"); break;
            case 138: return __("Gateway message error"); break;
            case 139: return __("Gateway format error"); break;
            case 140: return __("Credit card does not exist"); break;
            case 141: return __("Amount zero or not valid"); break;
            case 142: return __("Operation canceled"); break;
            case 143: return __("Authentification error"); break;
            case 144: return __("Denegation by security level"); break;
            case 145: return __("Error in PUC message. Please contact PAYCOMET"); break;
            case 146: return __("System error"); break;
            case 147: return __("Duplicated transaction"); break;
            case 148: return __("MAC error"); break;
            case 149: return __("Settlement rejected"); break;
            case 150: return __("System date/time not synchronized"); break;
            case 151: return __("Invalid card expiration date"); break;
            case 152: return __("Could not find any preauthorization with given data"); break;
            case 153: return __("Cannot find requested data"); break;
            case 154: return __("Cannot operate with given credit card"); break;
            case 155: return __("This method requires activation of the VHASH protocol"); break;
            case 500: return __("Unexpected error"); break;
            case 501: return __("Unexpected error"); break;
            case 502: return __("Unexpected error"); break;
            case 504: return __("Transaction already cancelled"); break;
            case 505: return __("Transaction originally denied"); break;
            case 506: return __("Confirmation data not valid"); break;
            case 507: return __("Unexpected error"); break;
            case 508: return __("Transaction still in process"); break;
            case 509: return __("Unexpected error"); break;
            case 510: return __("Refund is not possible"); break;
            case 511: return __("Unexpected error"); break;
            case 512: return __("Card issuer not available right now. Please try again later"); break;
            case 513: return __("Unexpected error"); break;
            case 514: return __("Unexpected error"); break;
            case 515: return __("Unexpected error"); break;
            case 516: return __("Unexpected error"); break;
            case 517: return __("Unexpected error"); break;
            case 518: return __("Unexpected error"); break;
            case 519: return __("Unexpected error"); break;
            case 520: return __("Unexpected error"); break;
            case 521: return __("Unexpected error"); break;
            case 522: return __("Unexpected error"); break;
            case 523: return __("Unexpected error"); break;
            case 524: return __("Unexpected error"); break;
            case 525: return __("Unexpected error"); break;
            case 526: return __("Unexpected error"); break;
            case 527: return __("TransactionType desconocido"); break;
            case 528: return __("Unexpected error"); break;
            case 529: return __("Unexpected error"); break;
            case 530: return __("Unexpected error"); break;
            case 531: return __("Unexpected error"); break;
            case 532: return __("Unexpected error"); break;
            case 533: return __("Unexpected error"); break;
            case 534: return __("Unexpected error"); break;
            case 535: return __("Unexpected error"); break;
            case 536: return __("Unexpected error"); break;
            case 537: return __("Unexpected error"); break;
            case 538: return __("Not cancelable operation"); break;
            case 539: return __("Unexpected error"); break;
            case 540: return __("Unexpected error"); break;
            case 541: return __("Unexpected error"); break;
            case 542: return __("Unexpected error"); break;
            case 543: return __("Unexpected error"); break;
            case 544: return __("Unexpected error"); break;
            case 545: return __("Unexpected error"); break;
            case 546: return __("Unexpected error"); break;
            case 547: return __("Unexpected error"); break;
            case 548: return __("Unexpected error"); break;
            case 549: return __("Unexpected error"); break;
            case 550: return __("Unexpected error"); break;
            case 551: return __("Unexpected error"); break;
            case 552: return __("Unexpected error"); break;
            case 553: return __("Unexpected error"); break;
            case 554: return __("Unexpected error"); break;
            case 555: return __("Could not find the previous operation"); break;
            case 556: return __("Data inconsistency in cancellation validation"); break;
            case 557: return __("Delayed payment code does not exists"); break;
            case 558: return __("Unexpected error"); break;
            case 559: return __("Unexpected error"); break;
            case 560: return __("Unexpected error"); break;
            case 561: return __("Unexpected error"); break;
            case 562: return __("Credit card does not allow preauthorizations"); break;
            case 563: return __("Data inconsistency in confirmation"); break;
            case 564: return __("Unexpected error"); break;
            case 565: return __("Unexpected error"); break;
            case 567: return __("Refund operation not correctly specified"); break;
            case 568: return __("Online communication incorrect"); break;
            case 569: return __("Denied operation"); break;
            case 1000: return __("Account not found. Review your settings"); break;
            case 1001: return __("User not found. Please contact your administrator"); break;
            case 1002: return __("External provider signature error. Contact your service provider"); break;
            case 1003: return __("Signature not valid. Please review your settings"); break;
            case 1004: return __("Forbidden access"); break;
            case 1005: return __("Invalid credit card format"); break;
            case 1006: return __("Data error: Validation code"); break;
            case 1007: return __("Data error: Expiration date"); break;
            case 1008: return __("Preauthorization reference not found"); break;
            case 1009: return __("Preauthorization data could not be found"); break;
            case 1010: return __("Could not send cancellation. Please try again later"); break;
            case 1011: return __("Could not connect to host"); break;
            case 1012: return __("Could not resolve proxy address"); break;
            case 1013: return __("Could not resolve host"); break;
            case 1014: return __("Initialization failed"); break;
            case 1015: return __("Could not find HTTP resource"); break;
            case 1016: return __("The HTTP options range is not valid"); break;
            case 1017: return __("The POST is not correctly built"); break;
            case 1018: return __("The username is not correctly formatted"); break;
            case 1019: return __("Operation timeout exceeded"); break;
            case 1020: return __("Insufficient memory"); break;
            case 1021: return __("Could not connect to SSL host"); break;
            case 1022: return __("Protocol not supported"); break;
            case 1023: return __("Given URL is not correctly formatted and cannot be used"); break;
            case 1024: return __("URL user is not correctly formatted"); break;
            case 1025: return __("Cannot register available resources to complete current operation"); break;
            case 1026: return __("Duplicated external reference"); break;
            case 1027: return __("Total refunds cannot exceed original payment"); break;
            case 1028: return __("Account not active. Please contact PAYCOMET"); break;
            case 1029: return __("Account still not certified. Please contact PAYCOMET"); break;
            case 1030: return __("Product is marked for deletion and cannot be used"); break;
            case 1031: return __("Insufficient rights"); break;
            case 1032: return __("Product cannot be used under test environment"); break;
            case 1033: return __("Product cannot be used under production environment"); break;
            case 1034: return __("It was not possible to send the refund request"); break;
            case 1035: return __("Error in field operation origin IP"); break;
            case 1036: return __("Error in XML format"); break;
            case 1037: return __("Root element is not correct"); break;
            case 1038: return __("Field DS_MERCHANT_AMOUNT incorrect"); break;
            case 1039: return __("Field DS_MERCHANT_ORDER incorrect"); break;
            case 1040: return __("Field DS_MERCHANT_MERCHANTCODE incorrect"); break;
            case 1041: return __("Field DS_MERCHANT_CURRENCY incorrect"); break;
            case 1042: return __("Field DS_MERCHANT_PAN incorrect"); break;
            case 1043: return __("Field DS_MERCHANT_CVV2 incorrect"); break;
            case 1044: return __("Field DS_MERCHANT_TRANSACTIONTYPE incorrect"); break;
            case 1045: return __("Field DS_MERCHANT_TERMINAL incorrect"); break;
            case 1046: return __("Field DS_MERCHANT_EXPIRYDATE incorrect"); break;
            case 1047: return __("Field DS_MERCHANT_MERCHANTSIGNATURE incorrect"); break;
            case 1048: return __("Field DS_ORIGINAL_IP incorrect"); break;
            case 1049: return __("Client not found"); break;
            case 1050: return __("Preauthorization amount cannot be greater than previous preauthorization amount"); break;
            case 1099: return __("Unexpected error"); break;
            case 1100: return __("Card diary limit exceeds"); break;
            case 1103: return __("ACCOUNT field error"); break;
            case 1104: return __("USERCODE field error"); break;
            case 1105: return __("TERMINAL field error"); break;
            case 1106: return __("OPERATION field error"); break;
            case 1107: return __("REFERENCE field error"); break;
            case 1108: return __("AMOUNT field error"); break;
            case 1109: return __("CURRENCY field error"); break;
            case 1110: return __("SIGNATURE field error"); break;
            case 1120: return __("Operation unavailable"); break;
            case 1121: return __("Client not found"); break;
            case 1122: return __("User not found. Contact PAYCOMET"); break;
            case 1123: return __("Invalid signature. Please check your configuration"); break;
            case 1124: return __("Operation not available with the specified user"); break;
            case 1125: return __("Invalid operation in a currency other than Euro"); break;
            case 1127: return __("Quantity zero or invalid"); break;
            case 1128: return __("Current currency conversion invalid"); break;
            case 1129: return __("Invalid amount"); break;
            case 1130: return __("Product not found"); break;
            case 1131: return __("Invalid operation with the current currency"); break;
            case 1132: return __("Invalid operation with a different article of the Euro currency"); break;
            case 1133: return __("Info button corrupt"); break;
            case 1134: return __("The subscription may not exceed the expiration date of the card"); break;
            case 1135: return __("DS_EXECUTE can not be true if DS_SUBSCRIPTION_STARTDATE is different from today."); break;
            case 1136: return __("PAYCOMET_OPERATIONS_MERCHANTCODE field error"); break;
            case 1137: return __("PAYCOMET_OPERATIONS_TERMINAL must be Array"); break;
            case 1138: return __("PAYCOMET_OPERATIONS_OPERATIONS must be Array"); break;
            case 1139: return __("PAYCOMET_OPERATIONS_SIGNATURE field error"); break;
            case 1140: return __("Can not find any of the PAYCOMET_OPERATIONS_TERMINAL"); break;
            case 1141: return __("Error in the date range requested"); break;
            case 1142: return __("The application can not have a length greater than 2 years"); break;
            case 1143: return __("The operation state is incorrect"); break;
            case 1144: return __("Error in the amounts of the search"); break;
            case 1145: return __("The type of operation requested does not exist"); break;
            case 1146: return __("Sort Order unrecognized"); break;
            case 1147: return __("PAYCOMET_OPERATIONS_SORTORDER unrecognized"); break;
            case 1148: return __("Subscription start date wrong"); break;
            case 1149: return __("Subscription end date wrong"); break;
            case 1150: return __("Frequency error in the subscription"); break;
            case 1151: return __("Invalid usuarioXML"); break;
            case 1152: return __("Invalid codigoCliente"); break;
            case 1153: return __("Invalid usuarios parameter"); break;
            case 1154: return __("Invalid firma parameter"); break;
            case 1155: return __("Invalid usuarios parameter format"); break;
            case 1156: return __("Invalid type"); break;
            case 1157: return __("Invalid name"); break;
            case 1158: return __("Invalid surname"); break;
            case 1159: return __("Invalid email"); break;
            case 1160: return __("Invalid password"); break;
            case 1161: return __("Invalid language"); break;
            case 1162: return __("Invalid maxamount"); break;
            case 1163: return __("Invalid multicurrency"); break;
            case 1165: return __("Invalid permissions_specs. Format not allowed"); break;
            case 1166: return __("Invalid permissions_products. Format not allowed"); break;
            case 1167: return __("Invalid email. Format not allowed"); break;
            case 1168: return __("Weak or invalid password"); break;
            case 1169: return __("Invalid value for type parameter"); break;
            case 1170: return __("Invalid value for language parameter"); break;
            case 1171: return __("Invalid format for maxamount parameter"); break;
            case 1172: return __("Invalid multicurrency. Format not allowed"); break;
            case 1173: return __("Invalid permission_id – permissions_specs. Not allowed"); break;
            case 1174: return __("Invalid user"); break;
            case 1175: return __("Invalid credentials"); break;
            case 1176: return __("Account not found"); break;
            case 1177: return __("User not found"); break;
            case 1178: return __("Invalid signature"); break;
            case 1179: return __("Account without products"); break;
            case 1180: return __("Invalid product_id - permissions_products. Not allowed"); break;
            case 1181: return __("Invalid permission_id -permissions_products. Not allowed"); break;
            case 1185: return __("Minimun limit not allowed"); break;
            case 1186: return __("Maximun limit not allowed"); break;
            case 1187: return __("Daily limit not allowed"); break;
            case 1188: return __("Monthly limit not allowed"); break;
            case 1189: return __("Max amount (same card / last 24 h.) not allowed"); break;
            case 1190: return __("Max amount (same card / last 24 h. / same IP address) not allowed"); break;
            case 1191: return __("Day / IP address limit (all cards) not allowed"); break;
            case 1192: return __("Country (merchant IP address) not allowed"); break;
            case 1193: return __("Card type (credit / debit) not allowed"); break;
            case 1194: return __("Card brand not allowed"); break;
            case 1195: return __("Card Category not allowed"); break;
            case 1196: return __("Authorization from different country than card issuer, not allowed"); break;
            case 1197: return __("Denied. Filter: Card country issuer not allowed"); break;
            case 1198: return __("Scoring limit exceeded"); break;
            case 1200: return __("Denied. Filter: same card, different country last 24 h."); break;
            case 1201: return __("Number of erroneous consecutive attempts with the same card exceeded"); break;
            case 1202: return __("Number of failed attempts (last 30 minutes) from the same ip address exceeded"); break;
            case 1203: return __("Wrong or not configured PayPal credentials"); break;
            case 1204: return __("Wrong token received"); break;
            case 1205: return __("Can not perform the operation"); break;
            case 1206: return __("ProviderID not available"); break;
            case 1207: return __("Operations parameter missing or not in a correct format"); break;
            case 1208: return __("PaycometMerchant parameter missing"); break;
            case 1209: return __("MerchatID parameter missing"); break;
            case 1210: return __("TerminalID parameter missing"); break;
            case 1211: return __("TpvID parameter missing"); break;
            case 1212: return __("OperationType parameter missing"); break;
            case 1213: return __("OperationResult parameter missing"); break;
            case 1214: return __("OperationAmount parameter missing"); break;
            case 1215: return __("OperationCurrency parameter missing"); break;
            case 1216: return __("OperationDatetime parameter missing"); break;
            case 1217: return __("OriginalAmount parameter missing"); break;
            case 1218: return __("Pan parameter missing"); break;
            case 1219: return __("ExpiryDate parameter missing"); break;
            case 1220: return __("Reference parameter missing"); break;
            case 1221: return __("Signature parameter missing"); break;
            case 1222: return __("OriginalIP parameter missing or not in a correct format"); break;
            case 1223: return __("Authcode / errorCode parameter missing"); break;
            case 1224: return __("Product of the operation missing"); break;
            case 1225: return __("The type of operation is not supported"); break;
            case 1226: return __("The result of the operation is not supported"); break;
            case 1227: return __("The transaction currency is not supported"); break;
            case 1228: return __("The date of the transaction is not in a correct format"); break;
            case 1229: return __("The signature is not correct"); break;
            case 1230: return __("Can not find the associated account information"); break;
            case 1231: return __("Can not find the associated product information"); break;
            case 1232: return __("Can not find the associated user information"); break;
            case 1233: return __("The product is not set as multicurrency"); break;
            case 1234: return __("The amount of the transaction is not in a correct format"); break;
            case 1235: return __("The original amount of the transaction is not in a correct format"); break;
            case 1236: return __("The card does not have the correct format"); break;
            case 1237: return __("The expiry date of the card is not in a correct format"); break;
            case 1238: return __("Can not initialize the service"); break;
            case 1239: return __("Can not initialize the service"); break;
            case 1240: return __("Method not implemented"); break;
            case 1241: return __("Can not initialize the service"); break;
            case 1242: return __("Service can not be completed"); break;
            case 1243: return __("OperationCode parameter missing"); break;
            case 1244: return __("bankName parameter missing"); break;
            case 1245: return __("csb parameter missing"); break;
            case 1246: return __("userReference parameter missing"); break;
            case 1247: return __("Can not find the associated FUC"); break;
            case 1248: return __("Duplicate xref. Pending operation."); break;
            case 1249: return __("[DS_]AGENT_FEE parameter missing"); break;
            case 1250: return __("[DS_]AGENT_FEE parameter is not in a correct format"); break;
            case 1251: return __("DS_AGENT_FEE parameter is not correct"); break;
            case 1252: return __("CANCEL_URL parameter missing"); break;
            case 1253: return __("CANCEL_URL parameter is not in a correct format"); break;
            case 1254: return __("Commerce with secure cardholder and cardholder without secure purchase key"); break;
            case 1255: return __("Call terminated by the client"); break;
            case 1256: return __("Call terminated, incorrect attempts exceeded"); break;
            case 1257: return __("Call terminated, operation attempts exceeded"); break;
            case 1258: return __("stationID not available"); break;
            case 1259: return __("It has not been possible to establish the IVR session"); break;
            case 1260: return __("merchantCode parameter missing"); break;
            case 1261: return __("The merchantCode parameter is incorrect"); break;
            case 1262: return __("terminalIDDebtor parameter missing"); break;
            case 1263: return __("terminalIDCreditor parameter missing"); break;
            case 1264: return __("Authorisations for carrying out the operation not available"); break;
            case 1265: return __("The Iban account (terminalIDDebtor) is invalid"); break;
            case 1266: return __("The Iban account (terminalIDCreditor) is invalid"); break;
            case 1267: return __("The BicCode of the Iban account (terminalIDDebtor) is invalid"); break;
            case 1268: return __("The BicCode of the Iban account (terminalIDCreditor) is invalid"); break;
            case 1269: return __("operationOrder parameter missing"); break;
            case 1270: return __("The operationOrder parameter does not have the correct format"); break;
            case 1271: return __("The operationAmount parameter does not have the correct format"); break;
            case 1272: return __("The operationDatetime parameter does not have the correct format"); break;
            case 1273: return __("The operationConcept parameter contains invalid characters or exceeds 140 characters"); break;
            case 1274: return __("It has not been possible to record the SEPA operation"); break;
            case 1275: return __("It has not been possible to record the SEPA operation"); break;
            case 1276: return __("Can not create an operation token"); break;
            case 1277: return __("Invalid scoring value"); break;
            case 1278: return __("The language parameter is not in a correct format"); break;
            case 1279: return __("The cardholder name is not in a correct format"); break;
            case 1280: return __("The card does not have the correct format"); break;
            case 1281: return __("The month does not have the correct format"); break;
            case 1282: return __("The year does not have the correct format"); break;
            case 1283: return __("The cvc2 does not have the correct format"); break;
            case 1284: return __("The JETID parameter is not in a correct format"); break;
            case 1288: return __("The splitId parameter is not valid"); break;
            case 1289: return __("The splitId parameter is not allowed"); break;
            case 1290: return __("This terminal don't allow split transfers"); break;
            case 1291: return __("It has not been possible to record the split transfer operation"); break;
            case 1292: return __("Original payment's date cannot exceed 90 days"); break;
            case 1293: return __("Original split tansfer not found"); break;
            case 1294: return __("Total reversal cannot exceed original split transfer"); break;
            case 1295: return __("It has not been possible to record the split transfer reversal operation"); break;

        }

    }



    
}
