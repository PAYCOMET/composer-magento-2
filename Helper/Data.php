<?php

namespace Paycomet\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Paycomet\Payment\Observer\DataAssignObserver;
use Paycomet\Payment\Model\Config\Source\PaymentAction;
use Paycomet\Bankstore\ApiRest;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Paycomet\Payment\Controller\Cards\Update;

class Data extends AbstractHelper
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var\Magento\Framework\App\Action\Context
     */
    private $_context2;

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

    /**
     * @var CustomerInterfaceFactory
     */
    private $_customerFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                             $context
     * @param \Magento\Framework\App\Action\Context                             $context2
     * @param \Magento\Framework\Encryption\EncryptorInterface                  $encryptor
     * @param \Magento\Directory\Model\Config\Source\Country                    $country
     * @param \Magento\Quote\Api\CartRepositoryInterface                        $quoteRepository
     * @param \Magento\Framework\Module\ModuleListInterface                     $moduleList
     * @param \Magento\Store\Model\StoreManagerInterface                        $storeManager
     * @param \Paycomet\Payment\Logger\Logger                                   $paycometLogger
     * @param \Magento\Framework\App\ProductMetadataInterface                   $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                       $resourceInterface
     * @param \Magento\Framework\Locale\ResolverInterface                       $resolver
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                 $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface                       $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory                  $orderHistoryFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder                      $searchCriteriaBuilder
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface   $transactionBuilder
     * @param \Paycomet\Payment\Logger\Logger                                   $logger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender               $orderSender
     * @param \Magento\Customer\Model\Session                                   $session
     * @param RemoteAddress                                                     $remoteAddress
     * @param \Magento\Customer\Model\CustomerFactory                           $customerFactory
     * @param \Magento\Framework\ObjectManagerInterface                         $objectmanager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Action\Context $context2,
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
        RemoteAddress $remoteAddress,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->_context2 = $context2;
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
        $this->_objectManager = $objectmanager;
        $this->_remoteAddress = $remoteAddress;
    }

    /**
     * Sign Fields
     *
     * @param string $fields
     * @param string $account
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
     * Checkout getURLOK.
     *
     * @param int $orderid
     * @return string
     */
    private function getAddUserURLOK($orderid)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/view',
            $this->_buildAddUserSessionParams(true, $orderid)
        );
    }

    /**
     * Checkout getURLKO.
     *
     * @param int $orderid
     * @return string
     */
    private function getAddUserURLKO($orderid)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/cards/view',
            $this->_buildAddUserSessionParams(false, $orderid)
        );
    }

    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     * @param int $orderid
     *
     * @return array
     */
    public function _buildAddUserSessionParams($result, $orderid)
    {
        $result = ($result) ? '1' : '0';
        $timestamp = date('YmdHMS');
        $merchant_code = $this->getConfigData('merchant_code');
        $sha1hash = $this->signFields("$timestamp.$merchant_code.$orderid.$result");

        return ['timestamp' => $timestamp, 'order_id' => $orderid, 'result' => $result, 'hash' => $sha1hash];
    }

    /**
     * Apm Execute Purchase
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param int $methodId
     *
     * @return array
     */
    public function apmExecutePurchase($order, $methodId)
    {
        $storeId = $order->getStoreId();
        $merchant_terminal  = trim($this->getConfigData('merchant_terminal', $storeId));
        $api_key            = trim($this->getEncryptedConfigData('api_key', $storeId));

        $realOrderId = $order->getRealOrderId();

        if ($api_key != "") {
            $merchantData = $this->getMerchantData($order, $methodId);
            $apiRest = new ApiRest($api_key);
            try {

                $orderCurrencyCode = $order->getBaseCurrencyCode();
                $amount = $this->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);

                $secure = 1;
                $userInteraction = 1;
                $notifyDirectPayment = 1;

                return $apiRest->executePurchase(
                    $merchant_terminal,
                    $realOrderId,
                    $amount,
                    $orderCurrencyCode,
                    $methodId,
                    $this->_remoteAddress->getRemoteAddress(),
                    $secure,
                    '',
                    '',
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

            } catch (\Exception $e) {
                $this->logDebug("Error in apmExecutePurchase: " . $e->getMessage());
            }
        }

        $objAux = $this->objectFactory->create();
        $objAux->setData('errorCode', 104);
        return $objAux;
    }

    /**
     * Add User Token
     *
     * @param string $jetToken
     */
    public function addUserToken($jetToken)
    {

        $merchant_terminal  = trim($this->getConfigData('merchant_terminal'));
        $api_key            = trim($this->getEncryptedConfigData('api_key'));

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
                    $response["IdUser"] = $tokenCard->idUser ?? 0;
                    $response["TokenUser"] = $tokenCard->tokenUser ?? '';
                }

                $customerId = $this->getCustomerId();
                $storeId = $this->_storeManager->getStore()->getId();

                $this->_handleCardStorage($response, $customerId, $storeId);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('jetToken card failed'));
            }
        } else {
            $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
        }
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
        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->amountFromMagento($amount, $orderCurrencyCode);
        $AuthCode = $payment->getTransactionId();
        $AuthCode = str_replace("-refund", "", $AuthCode);
        $AuthCode = str_replace("-capture", "", $AuthCode);
        $storeId = $order->getStoreId();

        $merchant_terminal  = trim($this->getConfigData('merchant_terminal', $storeId));
        $api_key            = trim($this->getEncryptedConfigData('api_key', $storeId));

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

            $response = [];
            $response["DS_RESPONSE"] = ($executeRefundReponse->errorCode > 0)? 0 : 1;
            $response["DS_ERROR_ID"] = $executeRefundReponse->errorCode;

            if ($response["DS_RESPONSE"]==1) {
                $response["DS_MERCHANT_AUTHCODE"] = $executeRefundReponse->authCode ?? $payment->getParentTransactionId();
            }
        } else {
            $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
        }

        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    sprintf(
                        'Refund failed. Error ( %s ) - %s',
                        $response['DS_ERROR_ID'],
                        $this->getErrorDesc($response['DS_ERROR_ID'])
                    )
                )
            );
        } else {
            $payment->setTransactionId($response['DS_MERCHANT_AUTHCODE'] . '-refund' . date("YmdHis"))
                    ->setParentTransactionId($AuthCode)
                    ->setTransactionAdditionalInfo(
                        \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                        $response
                    );
        }
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

        $order = $payment->getOrder();
        $realOrderId = $order->getRealOrderId();

        $orderCurrencyCode = $order->getBaseCurrencyCode();

        $amount = $this->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);
        $AuthCode = $payment->getTransactionId();
        $AuthCode = str_replace("-void", "", $payment->getTransactionId());

        $storeId = $order->getStoreId();

        $merchant_terminal  = trim($this->getConfigData('merchant_terminal', $storeId));
        $api_key            = trim($this->getEncryptedConfigData('api_key', $storeId));

        $notifyDirectPayment = 2;

        // Uso de Rest
        if ($api_key != "") {
            try {
                $apiRest = new ApiRest($api_key);

                $cancelPreautorization = $apiRest->cancelPreautorization(
                    $realOrderId,
                    $merchant_terminal,
                    $amount,
                    $this->_remoteAddress->getRemoteAddress(),
                    $AuthCode,
                    0,
                    $notifyDirectPayment
                );

                $response = [];
                $response["DS_RESPONSE"] = ($cancelPreautorization->errorCode > 0)? 0 : 1;
                $response["DS_ERROR_ID"] = $cancelPreautorization->errorCode;

                if ($response["DS_RESPONSE"]==1) {
                    $response["DS_MERCHANT_AUTHCODE"] = $AuthCode;
                    $response["DS_MERCHANT_AMOUNT"] = $cancelPreautorization->amount;
                }

            } catch (\Exception $e) {
                $response["DS_RESPONSE"] = 0;
                $response["DS_ERROR_ID"] = $cancelPreautorization->errorCode;
            }

        } else {
            $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
        }

        $response = (array) $response;
        if ('' == $response['DS_RESPONSE'] || 0 == $response['DS_RESPONSE']) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    sprintf(
                        'Cancel Preaut failed. Error ( %s ) - %s',
                        $response['DS_ERROR_ID'],
                        $this->getErrorDesc($response['DS_ERROR_ID'])
                    )
                )
            );
        }
        $payment->setTransactionId($response['DS_MERCHANT_AUTHCODE'])
                ->setParentTransactionId($AuthCode)
                ->setTransactionAdditionalInfo(
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    $response
                );
    }

    /**
     * Get Paycomet AddUser URL
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

        $merchant_terminal  = trim((string) $this->getConfigData('merchant_terminal'));
        $api_key            = trim((string) $this->getEncryptedConfigData('api_key'));

        $fieldOrderId = $this->_session->getCustomer()->getId() . "_" .
            $this->_storeManager->getStore()->getId(); //UserId | StoreId

        $shopperLocale = $this->_resolver->getLocale();
        $language_data = explode("_", $shopperLocale);
        $language = $language_data[0];

        $dataResponse = [];

        // REST
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
                        'urlOk' => (string) $this->getAddUserURLOK($fieldOrderId),
                        'urlKo' => (string) $this->getAddUserURLKO($fieldOrderId)
                    ]
                );

                $dataResponse["error"]  = $formResponse->errorCode;
                if ($formResponse->errorCode == 0) {
                    $dataResponse["url"] = $formResponse->challengeUrl;
                }

            } catch (\Exception $e) {

                $dataResponse["error"]  = $e->getMessage();
                $this->logDebug("Error in Rest 107: " . $e->getMessage());
            }

        } else {
            $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
        }

        return $dataResponse;
    }

    /**
     * Get Merchant Data
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param int $methodId
     */
    public function getMerchantData($order, $methodId)
    {

        $MERCHANT_EMV3DS = $this->getEMV3DS($order);
        $SHOPPING_CART = $this->getShoppingCart($order);

        $datos = array_merge($MERCHANT_EMV3DS, $SHOPPING_CART);

        $datos = $this->getMerchatDataMethod($datos, $order, $methodId);

        return $datos;
    }

    /**
     * Get Merchant Data Method
     *
     * @param array $datos
     * @param \Magento\Sales\Mode\Order $order
     * @param int $methodId
     */
    private function getMerchatDataMethod($datos, $order, $methodId)
    {
        switch ($methodId) {
            default:
                break;
        }
        return $datos;
    }

    /**
     * IsoCode to Number
     *
     * @param string $code
     */
    private function isoCodeToNumber($code)
    {
        $isoCodeNumber = 724; // Default value;
        $arrCode = [
            "AF" => "004",
            "AX" => "248",
            "AL" => "008",
            "DE" => "276",
            "AD" => "020",
            "AO" => "024",
            "AI" => "660",
            "AQ" => "010",
            "AG" => "028",
            "SA" => "682",
            "DZ" => "012",
            "AR" => "032",
            "AM" => "051",
            "AW" => "533",
            "AU" => "036",
            "AT" => "040",
            "AZ" => "031",
            "BS" => "044",
            "BD" => "050",
            "BB" => "052",
            "BH" => "048",
            "BE" => "056",
            "BZ" => "084",
            "BJ" => "204",
            "BM" => "060",
            "BY" => "112",
            "BO" => "068",
            "BQ" => "535",
            "BA" => "070",
            "BW" => "072",
            "BR" => "076",
            "BN" => "096",
            "BG" => "100",
            "BF" => "854",
            "BI" => "108",
            "BT" => "064",
            "CV" => "132",
            "KH" => "116",
            "CM" => "120",
            "CA" => "124",
            "QA" => "634",
            "TD" => "148",
            "CL" => "52",
            "CN" => "156",
            "CY" => "196",
            "CO" => "170",
            "KM" => "174",
            "KP" => "408",
            "KR" => "410",
            "CI" => "384",
            "CR" => "188",
            "HR" => "191",
            "CU" => "192",
            "CW" => "531",
            "DK" => "208",
            "DM" => "212",
            "EC" => "218",
            "EG" => "818",
            "SV" => "222",
            "AE" => "784",
            "ER" => "232",
            "SK" => "703",
            "SI" => "705",
            "ES" => "724",
            "US" => "840",
            "EE" => "233",
            "ET" => "231",
            "PH" => "608",
            "FI" => "246",
            "FJ" => "242",
            "FR" => "250",
            "GA" => "266",
            "GM" => "270",
            "GE" => "268",
            "GH" => "288",
            "GI" => "292",
            "GD" => "308",
            "GR" => "300",
            "GL" => "304",
            "GP" => "312",
            "GU" => "316",
            "GT" => "320",
            "GF" => "254",
            "GG" => "831",
            "GN" => "324",
            "GW" => "624",
            "GQ" => "226",
            "GY" => "328",
            "HT" => "332",
            "HN" => "340",
            "HK" => "344",
            "HU" => "348",
            "IN" => "356",
            "ID" => "360",
            "IQ" => "368",
            "IR" => "364",
            "IE" => "372",
            "BV" => "074",
            "IM" => "833",
            "CX" => "162",
            "IS" => "352",
            "KY" => "136",
            "CC" => "166",
            "CK" => "184",
            "FO" => "234",
            "GS" => "239",
            "HM" => "334",
            "FK" => "238",
            "MP" => "580",
            "MH" => "584",
            "PN" => "612",
            "SB" => "090",
            "TC" => "796",
            "UM" => "581",
            "VG" => "092",
            "VI" => "850",
            "IL" => "376",
            "IT" => "380",
            "JM" => "388",
            "JP" => "392",
            "JE" => "832",
            "JO" => "400",
            "KZ" => "398",
            "KE" => "404",
            "KG" => "417",
            "KI" => "296",
            "KW" => "414",
            "LA" => "418",
            "LS" => "426",
            "LV" => "428",
            "LB" => "422",
            "LR" => "430",
            "LY" => "434",
            "LI" => "438",
            "LT" => "440",
            "LU" => "442",
            "MO" => "446",
            "MK" => "807",
            "MG" => "450",
            "MY" => "458",
            "MW" => "454",
            "MV" => "462",
            "ML" => "466",
            "MT" => "470",
            "MA" => "504",
            "MQ" => "474",
            "MU" => "480",
            "MR" => "478",
            "YT" => "175",
            "MX" => "484",
            "FM" => "583",
            "MD" => "498",
            "MC" => "492",
            "MN" => "496",
            "ME" => "499",
            "MS" => "500",
            "MZ" => "508",
            "MM" => "104",
            "NA" => "516",
            "NR" => "520",
            "NP" => "524",
            "NI" => "558",
            "NE" => "562",
            "NG" => "566",
            "NU" => "570",
            "NF" => "574",
            "NO" => "578",
            "NC" => "540",
            "NZ" => "554",
            "OM" => "512",
            "NL" => "528",
            "PK" => "586",
            "PW" => "585",
            "PS" => "275",
            "PA" => "591",
            "PG" => "598",
            "PY" => "600",
            "PE" => "604",
            "PF" => "258",
            "PL" => "616",
            "PT" => "620",
            "PR" => "630",
            "GB" => "826",
            "EH" => "732",
            "CF" => "140",
            "CZ" => "203",
            "CG" => "178",
            "CD" => "180",
            "DO" => "214",
            "RE" => "638",
            "RW" => "646",
            "RO" => "642",
            "RU" => "643",
            "WS" => "882",
            "AS" => "016",
            "BL" => "652",
            "KN" => "659",
            "SM" => "674",
            "MF" => "663",
            "PM" => "666",
            "VC" => "670",
            "SH" => "654",
            "LC" => "662",
            "ST" => "678",
            "SN" => "686",
            "RS" => "688",
            "SC" => "690",
            "SL" => "694",
            "SG" => "702",
            "SX" => "534",
            "SY" => "760",
            "SO" => "706",
            "LK" => "144",
            "SZ" => "748",
            "ZA" => "710",
            "SD" => "729",
            "SS" => "728",
            "SE" => "752",
            "CH" => "756",
            "SR" => "740",
            "SJ" => "744",
            "TH" => "764",
            "TW" => "158",
            "TZ" => "834",
            "TJ" => "762",
            "IO" => "086",
            "TF" => "260",
            "TL" => "626",
            "TG" => "768",
            "TK" => "772",
            "TO" => "776",
            "TT" => "780",
            "TN" => "788",
            "TM" => "795",
            "TR" => "792",
            "TV" => "798",
            "UA" => "804",
            "UG" => "800",
            "UY" => "858",
            "UZ" => "860",
            "VU" => "548",
            "VA" => "336",
            "VE" => "862",
            "VN" => "704",
            "WF" => "876",
            "YE" => "887",
            "DJ" => "262",
            "ZM" => "894",
            "ZW" => "716"
        ];

        if (isset($arrCode[$code])) {
            $isoCodeNumber = $arrCode[$code];
        }
        return $isoCodeNumber;
    }

    /**
     * IsoCode Phone Prefix
     *
     * @param string $code
     */
    private function isoCodePhonePrefix($code)
    {
        $isoCodePhonePrefix = 34;
        $arrCode = [
            "AC" => "247",
            "AD" => "376",
            "AE" => "971",
            "AF" => "93",
            "AG" => "268",
            "AI" => "264",
            "AL" => "355",
            "AM" => "374",
            "AN" => "599",
            "AO" => "244",
            "AR" => "54",
            "AS" => "684",
            "AT" => "43",
            "AU" => "61",
            "AW" => "297",
            "AX" => "358",
            "AZ" => "374",
            "AZ" => "994",
            "BA" => "387",
            "BB" => "246",
            "BD" => "880",
            "BE" => "32",
            "BF" => "226",
            "BG" => "359",
            "BH" => "973",
            "BI" => "257",
            "BJ" => "229",
            "BM" => "441",
            "BN" => "673",
            "BO" => "591",
            "BR" => "55",
            "BS" => "242",
            "BT" => "975",
            "BW" => "267",
            "BY" => "375",
            "BZ" => "501",
            "CA" => "1",
            "CC" => "61",
            "CD" => "243",
            "CF" => "236",
            "CG" => "242",
            "CH" => "41",
            "CI" => "225",
            "CK" => "682",
            "CL" => "56",
            "CM" => "237",
            "CN" => "86",
            "CO" => "57",
            "CR" => "506",
            "CS" => "381",
            "CU" => "53",
            "CV" => "238",
            "CX" => "61",
            "CY" => "392",
            "CY" => "357",
            "CZ" => "420",
            "DE" => "49",
            "DJ" => "253",
            "DK" => "45",
            "DM" => "767",
            "DO" => "809",
            "DZ" => "213",
            "EC" => "593",
            "EE" => "372",
            "EG" => "20",
            "EH" => "212",
            "ER" => "291",
            "ES" => "34",
            "ET" => "251",
            "FI" => "358",
            "FJ" => "679",
            "FK" => "500",
            "FM" => "691",
            "FO" => "298",
            "FR" => "33",
            "GA" => "241",
            "GB" => "44",
            "GD" => "473",
            "GE" => "995",
            "GF" => "594",
            "GG" => "44",
            "GH" => "233",
            "GI" => "350",
            "GL" => "299",
            "GM" => "220",
            "GN" => "224",
            "GP" => "590",
            "GQ" => "240",
            "GR" => "30",
            "GT" => "502",
            "GU" => "671",
            "GW" => "245",
            "GY" => "592",
            "HK" => "852",
            "HN" => "504",
            "HR" => "385",
            "HT" => "509",
            "HU" => "36",
            "ID" => "62",
            "IE" => "353",
            "IL" => "972",
            "IM" => "44",
            "IN" => "91",
            "IO" => "246",
            "IQ" => "964",
            "IR" => "98",
            "IS" => "354",
            "IT" => "39",
            "JE" => "44",
            "JM" => "876",
            "JO" => "962",
            "JP" => "81",
            "KE" => "254",
            "KG" => "996",
            "KH" => "855",
            "KI" => "686",
            "KM" => "269",
            "KN" => "869",
            "KP" => "850",
            "KR" => "82",
            "KW" => "965",
            "KY" => "345",
            "KZ" => "7",
            "LA" => "856",
            "LB" => "961",
            "LC" => "758",
            "LI" => "423",
            "LK" => "94",
            "LR" => "231",
            "LS" => "266",
            "LT" => "370",
            "LU" => "352",
            "LV" => "371",
            "LY" => "218",
            "MA" => "212",
            "MC" => "377",
            "MD"  > "533",
            "MD" => "373",
            "ME" => "382",
            "MG" => "261",
            "MH" => "692",
            "MK" => "389",
            "ML" => "223",
            "MM" => "95",
            "MN" => "976",
            "MO" => "853",
            "MP" => "670",
            "MQ" => "596",
            "MR" => "222",
            "MS" => "664",
            "MT" => "356",
            "MU" => "230",
            "MV" => "960",
            "MW" => "265",
            "MX" => "52",
            "MY" => "60",
            "MZ" => "258",
            "NA" => "264",
            "NC" => "687",
            "NE" => "227",
            "NF" => "672",
            "NG" => "234",
            "NI" => "505",
            "NL" => "31",
            "NO" => "47",
            "NP" => "977",
            "NR" => "674",
            "NU" => "683",
            "NZ" => "64",
            "OM" => "968",
            "PA" => "507",
            "PE" => "51",
            "PF" => "689",
            "PG" => "675",
            "PH" => "63",
            "PK" => "92",
            "PL" => "48",
            "PM" => "508",
            "PR" => "787",
            "PS" => "970",
            "PT" => "351",
            "PW" => "680",
            "PY" => "595",
            "QA" => "974",
            "RE" => "262",
            "RO" => "40",
            "RS" => "381",
            "RU" => "7",
            "RW" => "250",
            "SA" => "966",
            "SB" => "677",
            "SC" => "248",
            "SD" => "249",
            "SE" => "46",
            "SG" => "65",
            "SH" => "290",
            "SI" => "386",
            "SJ" => "47",
            "SK" => "421",
            "SL" => "232",
            "SM" => "378",
            "SN" => "221",
            "SO" => "252",
            "SO" => "252",
            "SR"  > "597",
            "ST" => "239",
            "SV" => "503",
            "SY" => "963",
            "SZ" => "268",
            "TA" => "290",
            "TC" => "649",
            "TD" => "235",
            "TG" => "228",
            "TH" => "66",
            "TJ" => "992",
            "TK" =>  "690",
            "TL" => "670",
            "TM" => "993",
            "TN" => "216",
            "TO" => "676",
            "TR" => "90",
            "TT" => "868",
            "TV" => "688",
            "TW" => "886",
            "TZ" => "255",
            "UA" => "380",
            "UG" =>  "256",
            "US" => "1",
            "UY" => "598",
            "UZ" => "998",
            "VA" => "379",
            "VC" => "784",
            "VE" => "58",
            "VG" => "284",
            "VI" => "340",
            "VN" => "84",
            "VU" => "678",
            "WF" => "681",
            "WS" => "685",
            "YE" => "967",
            "YT" => "262",
            "ZA" => "27","ZM" => "260",
            "ZW" => "263"
        ];

        if (isset($arrCode[$code])) {
            $isoCodePhonePrefix = $arrCode[$code];
        }
        return $isoCodePhonePrefix;
    }

    /**
     * IsoCode Phone Prefix
     *
     * @param string $phone
     */
    public function getPhonePrefix($phone)
    {
        $prefix_array = [
            '34', '44', '213', '376', '244', '1264', '1268', '54', '374', '297', '61', '43', '994', '1242', '973', '880',
            '1246', '375', '32', '501', '229', '1441', '975', '591', '387', '267', '55', '673', '359', '226', '257',
            '855', '237', '238', '1345', '236', '56', '86', '57', '269', '242', '682', '506', '385', '53', '90392',
            '357', '42', '45', '253', '1809', '1809', '593', '20', '503', '240', '291', '372', '251', '500', '298',
            '679', '358', '33', '594', '689', '241', '220', '7880', '49', '233', '350', '30', '299', '1473', '590',
            '671', '502', '224', '245', '592', '509', '504', '852', '36', '354', '91', '62', '98', '964', '353', '972',
            '39', '1876', '81', '962', '254', '686', '850', '82', '965', '996', '856', '371', '961', '266', '231',
            '218', '417', '370', '352', '853', '389', '261', '265', '60', '960', '223', '356', '692', '596', '222',
            '52', '691', '373', '377', '976', '1664', '212', '258', '95', '264', '674', '977', '31', '687', '64', '505',
             '227', '234', '683', '672', '670', '47', '968', '680', '507', '675', '595', '51', '63', '48', '351',
             '1787', '974', '262', '40', '250', '378', '239', '966', '221', '381', '248', '232', '65', '421', '386',
            '677', '252', '27', '94', '290', '1869', '1758', '249', '597', '268', '46', '41', '963', '886', '66',
            '228', '676', '1868', '216', '90', '993', '1649', '688', '256', '380', '971', '598', '678', '379', '58',
            '84', '681', '969', '967', '260', '263', '1', '7'
        ];
        foreach ($prefix_array as $key => $prefix) {
            if (substr($phone, 0, strlen($prefix)+1) == '+' . $prefix)
                return $prefix;
        }
    }

    /**
     * Get Emv3DS
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    private function getEMV3DS($order)
    {

        $s_cid = $order->getCustomerId();
        if ($s_cid == "") {
            $s_cid = 0;
        }

        $Merchant_EMV3DS = [];

        $billingAddressData = $order->getBillingAddress();
        $phone = "";
        if (!empty($billingAddressData)) {
            $phone = $billingAddressData->getTelephone();
        }

        $Merchant_EMV3DS["customer"]["id"] = (int)$s_cid;
        $Merchant_EMV3DS["customer"]["name"] = ($order->getCustomerFirstname())?
            $order->getCustomerFirstname():$billingAddressData->getFirstname();
        $Merchant_EMV3DS["customer"]["surname"] = ($order->getCustomerLastname())?
            $order->getCustomerLastname():$billingAddressData->getLastname();
        $Merchant_EMV3DS["customer"]["email"] = $order->getCustomerEmail();

        $shippingAddressData = $order->getShippingAddress();
        if ($shippingAddressData) {
            $streetData = $shippingAddressData->getStreet();
            $street0 = (isset($streetData[0]))? strtolower($streetData[0]) : "";
            $street1 = (isset($streetData[1]))? strtolower($streetData[1]) : "";
            $street2 = (isset($streetData[2]))? strtolower($streetData[2]) : "";
        }

        if ($phone!="") {
            $phone_prefix = (substr(trim($phone), 0, 1) == '+') ? $this->getPhonePrefix(trim($phone)) : $this->isoCodePhonePrefix($billingAddressData->getCountryId());
            if ($phone_prefix!="") {
                $arrDatosWorkPhone["cc"] = substr(preg_replace("/[^0-9]/", '', $phone_prefix), 0, 5);
                $arrDatosWorkPhone["subscriber"] = substr(preg_replace("/[^0-9]/", '', str_replace('+' . $phone_prefix, '', $phone)), 0, 15);
                $Merchant_EMV3DS["customer"]["workPhone"] = $arrDatosWorkPhone;
                $Merchant_EMV3DS["customer"]["mobilePhone"] = $arrDatosWorkPhone;
            }
        }

        $Merchant_EMV3DS["customer"]["firstBuy"] = ($this->getFirstOrder($order) == 0)?"no":"si";

        $Merchant_EMV3DS["shipping"]["shipAddrCity"] = ($shippingAddressData)?
            $shippingAddressData->getCity():"";
        $Merchant_EMV3DS["shipping"]["shipAddrCountry"] = ($shippingAddressData)?
            $shippingAddressData->getCountryId():"";

        if ($Merchant_EMV3DS["shipping"]["shipAddrCountry"]!="") {
            $Merchant_EMV3DS["shipping"]["shipAddrCountry"] = (int)$this->isoCodeToNumber(
                $Merchant_EMV3DS["shipping"]["shipAddrCountry"]
            );
        }

        $Merchant_EMV3DS["shipping"]["shipAddrLine1"] = ($shippingAddressData)?$street0:"";
        $Merchant_EMV3DS["shipping"]["shipAddrLine2"] = ($shippingAddressData)?$street1:"";
        $Merchant_EMV3DS["shipping"]["shipAddrLine3"] = ($shippingAddressData)?$street2:"";
        $Merchant_EMV3DS["shipping"]["shipAddrPostCode"] = ($shippingAddressData)?
            $shippingAddressData->getPostcode():"";
        /*
        $Merchant_EMV3DS["shipping"]["shipAddrState"] = ($shippingAddressData)?
            $shippingAddressData->getRegionId():"";     // ISO 3166-2
        */

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
            $Merchant_EMV3DS["billing"]["billAddrCountry"] = (int)$this->isoCodeToNumber(
                $Merchant_EMV3DS["billing"]["billAddrCountry"]
            );
        }
        $Merchant_EMV3DS["billing"]["billAddrLine1"] = ($billingAddressData)?$street0:"";
        $Merchant_EMV3DS["billing"]["billAddrLine2"] = ($billingAddressData)?$street1:"";
        $Merchant_EMV3DS["billing"]["billAddrLine3"] = ($billingAddressData)?$street2:"";
        $Merchant_EMV3DS["billing"]["billAddrPostCode"] = ($billingAddressData)?$billingAddressData->getPostcode():"";

        /* ISO 3166-2
        $Merchant_EMV3DS["billing"]["billAddrState"] = ($billingAddressData)?$billingAddressData->getRegion():"";
        */

        // acctInfo
        $Merchant_EMV3DS["acctInfo"] = $this->acctInfo($order);

        // threeDSRequestorAuthenticationInfo
        $Merchant_EMV3DS["threeDSRequestorAuthenticationInfo"] = $this->threeDSRequestorAuthenticationInfo();

        // AddrMatch
        if ($order->getBillingAddress() && $order->getShippingAddress()) {
            $Merchant_EMV3DS["addrMatch"] = (
                $order->getBillingAddress()->getData('customer_address_id') ==
                $order->getShippingAddress()->getData('customer_address_id')
                )?"Y":"N";
        }

        $Merchant_EMV3DS["challengeWindowSize"] = 05;

        return $Merchant_EMV3DS;
    }

    /**
     * Acct Info
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    private function acctInfo($order)
    {

        $acctInfoData = [];
        $date_now = new \DateTime("now");

        $isGuest = $order->getCustomerIsGuest();
        if ($isGuest) {
            $acctInfoData["chAccAgeInd"] = "01";
        } else {

            $customer = $this->getCustomerById($order->getCustomerId());
            $date_customer = new \DateTime($customer->getCreatedAt());

            $diff = $date_now->diff($date_customer);
            $dias = $diff->days;

            if ($dias==0) {
                $acctInfoData["chAccAgeInd"] = "02";
            } elseif ($dias < 30) {
                $acctInfoData["chAccAgeInd"] = "03";
            } elseif ($dias < 60) {
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
            } elseif ($dias_upd < 30) {
                $acctInfoData["chAccChangeInd"] = "02";
            } elseif ($dias_upd < 60) {
                $acctInfoData["chAccChangeInd"] = "03";
            } else {
                $acctInfoData["chAccChangeInd"] = "04";
            }

            $chAccDate = new \DateTime($customer->getCreatedAt());
            $acctInfoData["chAccDate"] = $chAccDate->format('Ymd');

            $acctInfoData["nbPurchaseAccount"] = $this->numPurchaseCustomer($order->getCustomerId(), 1, 6, "month");
            //$acctInfoData["provisionAttemptsDay"] = "";

            $acctInfoData["txnActivityDay"] = $this->numPurchaseCustomer($order->getCustomerId(), 0, 1, "day");
            $acctInfoData["txnActivityYear"] = $this->numPurchaseCustomer($order->getCustomerId(), 0, 1, "year");

            if ($order->getShippingAddress()) {
                $firstAddressDelivery = $this->firstAddressDelivery(
                    $order->getCustomerId(),
                    $order->getShippingAddress()->getData('customer_address_id')
                );

                if ($firstAddressDelivery!="") {
                    $acctInfoData["shipAddressUsage"] = date("Ymd", strtotime($firstAddressDelivery));

                    $date_firstAddressDelivery = new \DateTime($firstAddressDelivery);
                    $diff = $date_now->diff($date_firstAddressDelivery);
                    $dias_firstAddressDelivery = $diff->days;

                    if ($dias_firstAddressDelivery==0) {
                        $acctInfoData["shipAddressUsageInd"] = "01";
                    } elseif ($dias_upd < 30) {
                        $acctInfoData["shipAddressUsageInd"] = "02";
                    } elseif ($dias_upd < 60) {
                        $acctInfoData["shipAddressUsageInd"] = "03";
                    } else {
                        $acctInfoData["shipAddressUsageInd"] = "04";
                    }
                }
            }
        }

        if ($order->getShippingAddress() &&
            (
                (
                    ($order->getCustomerFirstname() != "") &&
                    ($order->getCustomerFirstname() != $order->getShippingAddress()->getData('firstname'))
                ) ||
                (
                    ($order->getCustomerLastname() != "") &&
                    ($order->getCustomerLastname() != $order->getShippingAddress()->getData('lastname'))
                )
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
     *
     * @param int $id_customer
     * @param int $valid
     * @param int $interval
     * @param string $intervalType
     */
    private function numPurchaseCustomer($id_customer, $valid = 1, $interval = 1, $intervalType = "day")
    {

        try {
            $from = new \DateTime("now");
            $from->modify('-' . $interval . ' ' . $intervalType);

            $from = $from->format('Y-m-d h:m:s');

            if ($valid==1) {
                $orderCollection = $this->_objectManager->get(\Magento\Sales\Model\Order::class)->getCollection()
                    ->addFieldToFilter('customer_id', ['eq' => [$id_customer]])
                    ->addFieldToFilter('status', [
                        'nin' => ['pending','cancel','canceled','refund'],
                        'notnull'=>true])
                    ->addAttributeToFilter('created_at', ['gt' => $from]);
            } else {
                $orderCollection = $this->_objectManager->get(\Magento\Sales\Model\Order::class)->getCollection()
                    ->addFieldToFilter('customer_id', ['eq' => [$id_customer]])
                    ->addFieldToFilter('status', [
                        'notnull'=>true])
                    ->addAttributeToFilter('created_at', ['gt' => $from]);

            }
            return $orderCollection->getSize();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get Three DS Request Authentication Info
     */
    private function threeDSRequestorAuthenticationInfo()
    {

        $threeDSRequestorAuthenticationInfo = [];

        $logged = $this->customerIsLogged();
        $threeDSRequestorAuthenticationInfo["threeDSReqAuthMethod"] = ($logged)?"02":"01";

        if ($logged) {

            $lastVisited = new \DateTime($this->_session->getLoginAt() ?? '');
            $threeDSReqAuthTimestamp = $lastVisited->format('Ymdhm');
            $threeDSRequestorAuthenticationInfo["threeDSReqAuthTimestamp"] = $threeDSReqAuthTimestamp;
        }

        return $threeDSRequestorAuthenticationInfo;
    }

    /**
     * Obtiene Fecha del primer envio a una direccion
     *
     * @param int $id_customer codigo cliente
     * @param int $id_address_delivery direccion de envio
     **/

    private function firstAddressDelivery($id_customer, $id_address_delivery)
    {
        try {
            if ($id_customer && $id_address_delivery) {
                $resource = $this->_objectManager->get(\Magento\Framework\App\ResourceConnection::class);

                $orderCollection = $this->_objectManager->get(\Magento\Sales\Model\Order::class)->getCollection()
                ->addFieldToFilter('customer_id', ['eq' => $id_customer])
                ->getSelect()
                ->joinLeft(
                    $resource->getTableName('sales_order_address'),
                    "main_table.entity_id = " . $resource->getTableName('sales_order_address'). ".parent_id",
                    ['customer_address_id']
                )
                ->where($resource->getTableName('sales_order_address'). ".customer_address_id = $id_address_delivery ")
                ->limit('1')
                ->order('created_at ASC');

                $connection = $resource->getConnection();
                $results = $connection->fetchAll($orderCollection);

                if (count($results)>0) {
                    $firstOrder = current($results);
                    return $firstOrder["created_at"];
                }
            }
            return "";
        } catch (\Exception $e) {
            return "";
        }
    }

    /**
     * Get Shopping Cart
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    private function getShoppingCart($order)
    {
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $shoppingCartData = [];
        $amountAux = 0;

        try {
            foreach ($order->getAllItems() as $key => $item) {
                $shoppingCartData[$key]["sku"] = $item->getProductId();
                $shoppingCartData[$key]["articleType"] = 5;
                $shoppingCartData[$key]["quantity"] = (int) $item->getQtyOrdered();
                $shoppingCartData[$key]["unitPrice"] = $this->amountFromMagento($item->getPrice(), $orderCurrencyCode);
                $shoppingCartData[$key]["name"] = $item->getName();

                $amountAux += $shoppingCartData[$key]["unitPrice"] * $shoppingCartData[$key]["quantity"];

                $product = $this->_objectManager->create(\Magento\Catalog\Model\Product::class)->load($item->getProductId());                
                if ($product) {
                    $cats = $product->getCategoryIds();

                    $arrCat = [];
                    foreach ($cats as $category_id) {
                        $_cat = $this->_objectManager->create(\Magento\Catalog\Model\Category::class)->load($category_id);
                        if ($_cat) {
                            $arrCat[] = $_cat->getName();
                        }
                    }

                    $shoppingCartData[$key]["category"] = strip_tags(implode("|", $arrCat));
                }
            }

            // Shipping Cost
            $shippingAmount = $order->getShippingAmount();
            if ((int)$shippingAmount > 0) {
                $key++;
                $shoppingCartData[$key]["sku"] = "1";
                $shoppingCartData[$key]["articleType"] = "6";
                $shoppingCartData[$key]["quantity"] = 1;
                $shoppingCartData[$key]["unitPrice"] = $this->amountFromMagento($shippingAmount, $orderCurrencyCode);
                $shoppingCartData[$key]["name"] = "Package Shipping Cost";

                $amountAux += $shoppingCartData[$key]["unitPrice"] * $shoppingCartData[$key]["quantity"];
            }

            // Descuentos
            $discount = $this->amountFromMagento($order->getBaseDiscountAmount(), $orderCurrencyCode);

            if (isset($discount) && abs($discount)>0) {
                $key++;
                $shoppingCartData[$key]["sku"] = "1";
                $shoppingCartData[$key]["articleType"] = "4";
                $shoppingCartData[$key]["quantity"] = 1;
                $shoppingCartData[$key]["unitPrice"] = abs($discount);
                $shoppingCartData[$key]["name"] = "Discount";

                $amountAux -= abs($discount);
            }

            // Tax
            $amountTotal = $this->amountFromMagento($order->getBaseGrandTotal(), "EUR");
            $tax = $amountTotal - $amountAux;

            if ($tax > 0) {
                $key++;
                $shoppingCartData[$key]["sku"] = "1";
                $shoppingCartData[$key]["articleType"] = "11";
                $shoppingCartData[$key]["quantity"] = 1;
                $shoppingCartData[$key]["unitPrice"] = $tax;
                $shoppingCartData[$key]["name"] = "Tax";
            }
        } catch (\Exception $e) {
            // continue
        }

        return ["shoppingCart"=>$shoppingCartData];
    }

    /**
     * Get APM Paycomet URL
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param int $methodId
     * @return String URL
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAPMPaycometUrl($order, $methodId)
    {
        $merchant_terminal  = trim($this->getConfigData('merchant_terminal'));
        $api_key            = trim($this->getEncryptedConfigData('api_key'));

        if ($api_key == "") {
            throw new \Magento\Framework\Exception\LocalizedException(__('PAYCOMET API KEY required'));
        }

        $realOrderId = $order->getRealOrderId();
        $fieldOrderId = $realOrderId;

        $orderCurrencyCode = $order->getBaseCurrencyCode();
        $amount = $this->amountFromMagento($order->getBaseGrandTotal(), $orderCurrencyCode);

        $shopperLocale = $this->_resolver->getLocale();
        $language_data = explode("_", $shopperLocale);
        $language = $language_data[0];

        /** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId(null);
        $quoteRepository->save($quote);

        if ($api_key != "") {

            $OPERATION = 1;
            $Secure = 1;

            try {
                $apiRest = new ApiRest($api_key);
                $response = $apiRest->form(
                    $OPERATION,
                    $language,
                    $merchant_terminal,
                    '',
                    [
                        'terminal' => $merchant_terminal,
                        'methods' => [$methodId],
                        'order' => $fieldOrderId,
                        'amount' => $amount,
                        'currency' => $orderCurrencyCode,
                        'userInteraction' => 1,
                        'secure' => $Secure,
                        'merchantData' => $this->getMerchantData($order, $methodId),
                        'urlOk' => $this->getURLOK($order),
                        'urlKo' => $this->getURLKO($order)
                    ]
                );
                if ($response->errorCode==0) {
                    return $response->challengeUrl;
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Error: ' . $response->errorCode));
                }
                $response->DS_ERROR_ID = $response->errorCode;
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Error: ' . $e->getCode()));
            }
        } else {
            $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
            throw new \Magento\Framework\Exception\LocalizedException(__('Error: 1004'));
        }
    }

    /**
     * Checkout getURLOK.
     *
     * @param \Magento\Sales\Mode\Order $order
     * @return string
     */
    public function getURLOK($order)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/process/result',
            $this->_buildSessionParams(true, $order)
        );
    }

    /**
     * Checkout getURLKO.
     *
     * @param \Magento\Sales\Mode\Order $order
     * @return string
     */
    public function getURLKO($order)
    {
        return $this->_urlBuilder->getUrl(
            'paycomet_payment/process/result',
            $this->_buildSessionParams(false, $order)
        );
    }

    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     * @param \Magento\Sales\Mode\Order $order
     *
     * @return array
     */
    private function _buildSessionParams($result, $order)
    {
        $result = ($result) ? '1' : '0';
        $timestamp = date('YmdHMS');
        $merchant_code = $this->getConfigData('merchant_code');
        $orderid = $order->getRealOrderId();
        $sha1hash = $this->signFields("$timestamp.$merchant_code.$orderid.$result");

        return ['timestamp' => $timestamp, 'order_id' => $orderid, 'result' => $result, 'hash' => $sha1hash];
    }

    /**
     * Customer Is Logged
     */
    public function customerIsLogged()
    {
        return $this->_session->isLoggedIn();
    }

    /**
     * Log Debug
     *
     * @param string $message
     */
    public function logDebug($message)
    {
        if ($this->getConfigData('debug_log') == '1') {
            $this->_paycometLogger->debug($message);
        }
    }

    /**
     * Cancel Order
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    public function cancelOrder($order)
    {
        // Si ya esta cancelada no continuamos
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_CANCELED) {
            $this->_paycometLogger->debug("cancelOrder " . $order->getRealOrderId() . " - " . $order->getState() . " - Already Canceled");
            return;
        }

        if ($order->canCancel()) {
            $this->_paycometLogger->debug("cancelOrder " . $order->getRealOrderId() . " - " . $order->getState() . " - Canceling Order");

            $orderStatus = $this->getConfigData('payment_cancelled');
            $order->setActionFlag($orderStatus, true);
            $order->cancel()->save();
        } else {
            $this->_paycometLogger->debug("cancelOrder " . $order->getRealOrderId() . " - " . $order->getState() . " - Cannot Cancel Order");
        }
    }

    /**
     * Get quoteId
     *
     * @param object $quoteId
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote($quoteId)
    {
        // get quote from quoteId
        $quote = $this->_quoteRepository->get($quoteId);

        return $quote;
    }

    /**
     * Removes the response fields that we don't want stored
     *
     * @param array $response
     *
     * @return array
     */
    public function stripFields($response)
    {
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
     * Strips and trims the response and returns a new array of fields
     *
     * @param array $response
     *
     * @return array
     */
    public function stripTrimFields($response)
    {
        return $this->stripFields($response);
    }

    /**
     * Converts the magento decimal amount into a int one used by Paycomet
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
     * Converts the paycomet int amount into a decimal one used by Paycomet
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
     * Gets the amount of currency minor units.
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

    /**
     * Check First Minor Unit
     *
     * @param string $currencyCode
     */
    private function checkForFirstMinorUnit($currencyCode)
    {
        return in_array(
            $currencyCode,
            [
                'BYR',
                'BIF',
                'CLP',
                'DJF',
                'GNF',
                'ISK',
                'KMF',
                'KRW',
                'PYG',
                'RWF',
                'UGX',
                'UYI',
                'VUV',
                'VND',
                'XAF',
                'XOF',
                'XPF'
            ]
        );
    }

    /**
     * Sets additional information fields on the payment class
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
     * Gives back configuration values
     *
     * @param string $field
     * @param int $storeId
     * @param string $paymentMethodCode
     *
     * @return mixed
     */
    public function getConfigData(
        $field,
        $storeId = null,
        $paymentMethodCode = \Paycomet\Payment\Model\PaymentMethod::METHOD_CODE
    ) {
        $this->_paycometLogger->debug($field . "--" . $paymentMethodCode);
        return $this->getConfig($field, $paymentMethodCode, $storeId);
    }

    /**
     * Gives back configuration values as flag
     *
     * @param string $field
     * @param int $storeId
     * @param string $paymentMethodCode
     *
     * @return mixed
     */
    public function getConfigDataFlag(
        $field,
        $storeId = null,
        $paymentMethodCode = \Paycomet\Payment\Model\PaymentMethod::METHOD_CODE
    ) {
        return $this->getConfig($field, $paymentMethodCode, $storeId, true);
    }

    /**
     * Gives back encrypted configuration values
     *
     * @param string $field
     * @param int $storeId
     *
     * @return mixed
     */
    public function getEncryptedConfigData($field, $storeId = null)
    {
        return $this->_encryptor->decrypt(trim($this->getConfigData($field, $storeId) ?? ''));
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param string $paymentMethodCode
     * @param int $storeId
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

    /**
     * Get Customer Id
     */
    public function getCustomerId()
    {
        return $this->_session->getCustomer()->getId();
    }

    /**
     * Get Customer By Id
     *
     * @param int $id
     */
    public function getCustomerById($id)
    {
        return $this->_customerFactory->create()->load($id);
    }

    /**
     * Create Transaction
     *
     * @param string $type
     * @param int $transactionid
     * @param \Magento\Sales\Mode\Order $order
     * @param array $paymentData
     */
    public function createTransaction($type, $transactionid, $order = null, $paymentData = [])
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
                    $message = __(
                        'Captured amount of %1',
                        $order->getBaseCurrency()->formatTxt($order->getGrandTotal())
                    );
                    break;

                case \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH:
                    $message = __(
                        'Authorize amount of %1',
                        $order->getBaseCurrency()->formatTxt($order->getGrandTotal())
                    );
                    break;
            }

            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                                ->setOrder($order)
                                ->setTransactionId($transactionid)
                                ->setAdditionalInformation(
                                    [
                                        \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS =>
                                        (array) $paymentData
                                    ]
                                )
                                ->setFailSafe(true)
                                ->build($type);

            if ($type==\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
                $transaction->setIsClosed(false);
            }

            $order->setPayment($payment);

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                  ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

            $order->save();

            $this->_addHistoryComment($order, $message);

            return $transaction->save()->getTransactionId();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Create Transaction error'));
        }
    }

    /**
     * Create an invoice
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
     * Add a comment to order history
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

    /**
     * Get Token Data
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function getTokenData($payment)
    {
        $hash = $payment->getAdditionalInformation(DataAssignObserver::PAYCOMET_TOKENCARD);
        $customer_id = $this->getCustomerId();

        if ($hash=="" || $customer_id=="") {
            return null;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();

        $conds[] = $connection->quoteInto("hash" . ' = ?', $hash);
        $conds[] = $connection->quoteInto("customer_id" . ' = ?', $customer_id);
        $where = implode(' AND ', $conds);

        $select = $connection->select()
            ->from(
                ['token' => $resource->getTableName('paycomet_token')],
                ['iduser', 'tokenuser']
            )
            ->where($where);
        $data = $connection->fetchRow($select);

        return $data;
    }

    /**
     * Get First Order
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    public function getFirstOrder($order)
    {

        $searchCriteria = $this->_searchCriteriaBuilder
        ->addFilter('customer_id', $this->getCustomerId())
        ->addFilter('status', ['pending','cancel','canceled','refund'], 'nin')
        ->create();

        $orders = $this->_orderRepository->getList($searchCriteria);

        if (count($orders)>0) {
            return 0;
        }
        return 1;
    }

    /**
     * Is First Purchase token
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    public function isFirstPurchaseToken($payment)
    {
        $data = $this->getTokenData($payment);

        if (isset($data['iduser']) && isset($data['tokenuser'])) {
            $paycomet_token = $data['iduser'] . "|" . $data['tokenuser'];

            $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('customer_id', $this->getCustomerId())
            ->addFilter('paycomet_token', $paycomet_token)
            ->addFilter('status', ['pending_payment','pending','cancel','canceled','refund'], 'nin')
            ->create();

            $orders = $this->_orderRepository->getList($searchCriteria);

            if (count($orders)>0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create Transaction Invoice
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param array $response
     */
    public function createTransInvoice($order, $response)
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
        $IdUser = 0;
        $TokenUser = ""; // Inicializamos
        if (isset($response['IdUser']) && isset($response['TokenUser'])) {
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
     * Handles the card storage fields
     *
     * @param array $response
     * @param int $customerId
     * @param int $storeId
     */
    public function _handleCardStorage($response, $customerId, $storeId = null)
    {
        try {
            $IdUser = $response['IdUser'];
            $TokenUser = $response['TokenUser'];

            $merchant_terminal  = trim($this->getConfigData('merchant_terminal', $storeId));
            $api_key            = trim($this->getEncryptedConfigData('api_key', $storeId));

            if ($api_key != "") {
                $apiRest = new ApiRest($api_key);
                $formResponse = $apiRest->infoUser(
                    $IdUser,
                    $TokenUser,
                    $merchant_terminal
                );

                $resp = [];
                $resp["DS_MERCHANT_PAN"] = $formResponse->pan;
                $resp["DS_CARD_BRAND"] = $formResponse->cardBrand;
                $resp["DS_EXPIRYDATE"] = $formResponse->expiryDate;
                $resp["DS_ERROR_ID"] = 0;
            } else {
                $this->logDebug(__("ERROR: PAYCOMET API KEY required"));
            }
            if ('' == $resp['DS_ERROR_ID'] || 0 == $resp['DS_ERROR_ID']) {
                return $this->addCustomerCard($customerId, $IdUser, $TokenUser, $resp);
            } else {
                return false;
            }

        } catch (\Exception $e) {
            //card storage exceptions should not stop a transaction
            $this->_logger->critical($e);
        }
    }

    /**
     * Manage cards that were edited while the user was on payment
     *
     * @param int $customerId
     * @param int $IdUser
     * @param string $TokenUser
     * @param array $response
     */
    private function addCustomerCard($customerId, $IdUser, $TokenUser, $response)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();

            $card =  $response["DS_MERCHANT_PAN"];
            $card = 'XXXX-XXXX-XXXX-' . substr($card, -4);
            $card_brand =  $response["DS_CARD_BRAND"];
            $expiryDate = $response["DS_EXPIRYDATE"];

            $hash = hash('sha256', $IdUser . $TokenUser);

            $connection->insert(
                $resource->getTableName('paycomet_token'),
                [
                    'customer_id' => $customerId,
                    'hash' => $hash,
                    'iduser' => $IdUser,
                    'tokenuser' => $TokenUser,
                    'cc' => $card ,
                    'brand' => $card_brand,
                    'expiry' => $expiryDate,
                    'date' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
                ]
            );
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * Manage cards. Update expiryDate when not defined
     *
     * @param array $arrData
     * @return array $arrDataValidated array of valid and invalid cards
     */
    public function validateTokenInfo($arrData)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $arrDataValidated = [];
        $arrDataValidated["valid"] = [];
        $arrDataValidated["invalid"] = [];

        foreach ($arrData as $key => $tokenData) {
            if (empty($tokenData['expiry'])) {

                $merchant_terminal  = trim($this->getConfigData('merchant_terminal', $storeId));
                $api_key            = trim($this->getEncryptedConfigData('api_key', $storeId));

                if ($api_key != "") {
                    $apiRest = new ApiRest($api_key);
                    $formResponse = $apiRest->infoUser(
                        $tokenData['iduser'],
                        $tokenData['tokenuser'],
                        $merchant_terminal
                    );

                    if ($formResponse->errorCode==0) {
                        $tokenData['expiry'] = $formResponse->expiryDate;
                    } else {
                        $tokenData['expiry'] = "1900/01";
                    }

                    $update = new Update($this->_context2, $this->_session);
                    $update->updatePaycometCardExpiryDate(
                        $tokenData['hash'],
                        $tokenData['customer_id'],
                        $tokenData['expiry']
                    );
                }
            }

            // Remove sensible data from array
            unset($tokenData['customer_id']);
            unset($tokenData['iduser']);
            unset($tokenData['tokenuser']);

            // If not expired
            if ((int)date("Ym") < (int)str_replace("/", "", $tokenData['expiry'])) {
                $arrDataValidated["valid"][] = $tokenData;
            } else {
                if ($tokenData['expiry'] == "1900/01") {
                    $tokenData['expiry'] = "";
                }
                $arrDataValidated["invalid"][] = $tokenData;
            }
        }
        return $arrDataValidated;
    }

    /**
     * Get Error Description
     *
     * @param int $code
     */
    public function getErrorDesc($code)
    {
        switch ($code) {
            case 0:
                return __("No error");
            case 1:
                return __("Error");
            case 100:
                return __("Expired credit card");
            case 101:
                return __("Credit card blacklisted");
            case 102:
                return __("Operation not allowed for the credit card type");
            case 103:
                return __("Please, call the credit card issuer");
            case 104:
                return __("Unexpected error");
            case 105:
                return __("Insufficient funds");
            case 106:
                return __("Credit card not registered or not logged by the issuer");
            case 107:
                return __("Data error. Validation Code");
            case 108:
                return __("PAN Check Error");
            case 109:
                return __("Expiry date error");
            case 110:
                return __("Data error");
            case 111:
                return __("CVC2 block incorrect");
            case 112:
                return __("Please, call the credit card issuer");
            case 113:
                return __("Credit card not valid");
            case 114:
                return __("The credit card has credit restrictions");
            case 115:
                return __("Card issuer could not validate card owner");
            case 116:
                return __("Payment not allowed in off-line authorization");
            case 118:
                return __("Expired credit card. Please capture card");
            case 119:
                return __("Credit card blacklisted. Please capture card");
            case 120:
                return __("Credit card lost or stolen. Please capture card");
            case 121:
                return __("Error in CVC2. Please capture card");
            case 122:
                return __("Error en Pre-Transaction process. Try again later.");
            case 123:
                return __("Operation denied. Please capture card");
            case 124:
                return __("Closing with agreement");
            case 125:
                return __("Closing without agreement");
            case 126:
                return __("Cannot close right now");
            case 127:
                return __("Invalid parameter");
            case 128:
                return __("Transactions were not accomplished");
            case 129:
                return __("Duplicated internal reference");
            case 130:
                return __("Original operation not found. Could not refund");
            case 131:
                return __("Expired preauthorization");
            case 132:
                return __("Operation not valid with selected currency");
            case 133:
                return __("Error in message format");
            case 134:
                return __("Message not recognized by the system");
            case 135:
                return __("CVC2 block incorrect");
            case 137:
                return __("Credit card not valid");
            case 138:
                return __("Gateway message error");
            case 139:
                return __("Gateway format error");
            case 140:
                return __("Credit card does not exist");
            case 141:
                return __("Amount zero or not valid");
            case 142:
                return __("Operation canceled");
            case 143:
                return __("Authentification error");
            case 144:
                return __("Denegation by security level");
            case 145:
                return __("Error in PUC message. Please contact PAYCOMET");
            case 146:
                return __("System error");
            case 147:
                return __("Duplicated transaction");
            case 148:
                return __("MAC error");
            case 149:
                return __("Settlement rejected");
            case 150:
                return __("System date/time not synchronized");
            case 151:
                return __("Invalid card expiration date");
            case 152:
                return __("Could not find any preauthorization with given data");
            case 153:
                return __("Cannot find requested data");
            case 154:
                return __("Cannot operate with given credit card");
            case 155:
                return __("This method requires activation of the VHASH protocol");
            case 500:
                return __("Unexpected error");
            case 501:
                return __("Unexpected error");
            case 502:
                return __("Unexpected error");
            case 504:
                return __("Transaction already cancelled");
            case 505:
                return __("Transaction originally denied");
            case 506:
                return __("Confirmation data not valid");
            case 507:
                return __("Unexpected error");
            case 508:
                return __("Transaction still in process");
            case 509:
                return __("Unexpected error");
            case 510:
                return __("Refund is not possible");
            case 511:
                return __("Unexpected error");
            case 512:
                return __("Card issuer not available right now. Please try again later");
            case 513:
                return __("Unexpected error");
            case 514:
                return __("Unexpected error");
            case 515:
                return __("Unexpected error");
            case 516:
                return __("Unexpected error");
            case 517:
                return __("Unexpected error");
            case 518:
                return __("Unexpected error");
            case 519:
                return __("Unexpected error");
            case 520:
                return __("Unexpected error");
            case 521:
                return __("Unexpected error");
            case 522:
                return __("Unexpected error");
            case 523:
                return __("Unexpected error");
            case 524:
                return __("Unexpected error");
            case 525:
                return __("Unexpected error");
            case 526:
                return __("Unexpected error");
            case 527:
                return __("TransactionType desconocido");
            case 528:
                return __("Unexpected error");
            case 529:
                return __("Unexpected error");
            case 530:
                return __("Unexpected error");
            case 531:
                return __("Unexpected error");
            case 532:
                return __("Unexpected error");
            case 533:
                return __("Unexpected error");
            case 534:
                return __("Unexpected error");
            case 535:
                return __("Unexpected error");
            case 536:
                return __("Unexpected error");
            case 537:
                return __("Unexpected error");
            case 538:
                return __("Not cancelable operation");
            case 539:
                return __("Unexpected error");
            case 540:
                return __("Unexpected error");
            case 541:
                return __("Unexpected error");
            case 542:
                return __("Unexpected error");
            case 543:
                return __("Unexpected error");
            case 544:
                return __("Unexpected error");
            case 545:
                return __("Unexpected error");
            case 546:
                return __("Unexpected error");
            case 547:
                return __("Unexpected error");
            case 548:
                return __("Unexpected error");
            case 549:
                return __("Unexpected error");
            case 550:
                return __("Unexpected error");
            case 551:
                return __("Unexpected error");
            case 552:
                return __("Unexpected error");
            case 553:
                return __("Unexpected error");
            case 554:
                return __("Unexpected error");
            case 555:
                return __("Could not find the previous operation");
            case 556:
                return __("Data inconsistency in cancellation validation");
            case 557:
                return __("Delayed payment code does not exists");
            case 558:
                return __("Unexpected error");
            case 559:
                return __("Unexpected error");
            case 560:
                return __("Unexpected error");
            case 561:
                return __("Unexpected error");
            case 562:
                return __("Credit card does not allow preauthorizations");
            case 563:
                return __("Data inconsistency in confirmation");
            case 564:
                return __("Unexpected error");
            case 565:
                return __("Unexpected error");
            case 567:
                return __("Refund operation not correctly specified");
            case 568:
                return __("Online communication incorrect");
            case 569:
                return __("Denied operation");
            case 1000:
                return __("Account not found. Review your settings");
            case 1001:
                return __("User not found. Please contact your administrator");
            case 1002:
                return __("External provider signature error. Contact your service provider");
            case 1003:
                return __("Signature not valid. Please review your settings");
            case 1004:
                return __("Forbidden access");
            case 1005:
                return __("Invalid credit card format");
            case 1006:
                return __("Data error: Validation code");
            case 1007:
                return __("Data error: Expiration date");
            case 1008:
                return __("Preauthorization reference not found");
            case 1009:
                return __("Preauthorization data could not be found");
            case 1010:
                return __("Could not send cancellation. Please try again later");
            case 1011:
                return __("Could not connect to host");
            case 1012:
                return __("Could not resolve proxy address");
            case 1013:
                return __("Could not resolve host");
            case 1014:
                return __("Initialization failed");
            case 1015:
                return __("Could not find HTTP resource");
            case 1016:
                return __("The HTTP options range is not valid");
            case 1017:
                return __("The POST is not correctly built");
            case 1018:
                return __("The username is not correctly formatted");
            case 1019:
                return __("Operation timeout exceeded");
            case 1020:
                return __("Insufficient memory");
            case 1021:
                return __("Could not connect to SSL host");
            case 1022:
                return __("Protocol not supported");
            case 1023:
                return __("Given URL is not correctly formatted and cannot be used");
            case 1024:
                return __("URL user is not correctly formatted");
            case 1025:
                return __("Cannot register available resources to complete current operation");
            case 1026:
                return __("Duplicated external reference");
            case 1027:
                return __("Total refunds cannot exceed original payment");
            case 1028:
                return __("Account not active. Please contact PAYCOMET");
            case 1029:
                return __("Account still not certified. Please contact PAYCOMET");
            case 1030:
                return __("Product is marked for deletion and cannot be used");
            case 1031:
                return __("Insufficient rights");
            case 1032:
                return __("Product cannot be used under test environment");
            case 1033:
                return __("Product cannot be used under production environment");
            case 1034:
                return __("It was not possible to send the refund request");
            case 1035:
                return __("Error in field operation origin IP");
            case 1036:
                return __("Error in XML format");
            case 1037:
                return __("Root element is not correct");
            case 1038:
                return __("Field DS_MERCHANT_AMOUNT incorrect");
            case 1039:
                return __("Field DS_MERCHANT_ORDER incorrect");
            case 1040:
                return __("Field DS_MERCHANT_MERCHANTCODE incorrect");
            case 1041:
                return __("Field DS_MERCHANT_CURRENCY incorrect");
            case 1042:
                return __("Field DS_MERCHANT_PAN incorrect");
            case 1043:
                return __("Field DS_MERCHANT_CVV2 incorrect");
            case 1044:
                return __("Field DS_MERCHANT_TRANSACTIONTYPE incorrect");
            case 1045:
                return __("Field DS_MERCHANT_TERMINAL incorrect");
            case 1046:
                return __("Field DS_MERCHANT_EXPIRYDATE incorrect");
            case 1047:
                return __("Field DS_MERCHANT_MERCHANTSIGNATURE incorrect");
            case 1048:
                return __("Field DS_ORIGINAL_IP incorrect");
            case 1049:
                return __("Client not found");
            case 1050:
                return __("Preauthorization amount cannot be greater than previous preauthorization amount");
            case 1099:
                return __("Unexpected error");
            case 1100:
                return __("Card diary limit exceeds");
            case 1103:
                return __("ACCOUNT field error");
            case 1104:
                return __("USERCODE field error");
            case 1105:
                return __("TERMINAL field error");
            case 1106:
                return __("OPERATION field error");
            case 1107:
                return __("REFERENCE field error");
            case 1108:
                return __("AMOUNT field error");
            case 1109:
                return __("CURRENCY field error");
            case 1110:
                return __("SIGNATURE field error");
            case 1120:
                return __("Operation unavailable");
            case 1121:
                return __("Client not found");
            case 1122:
                return __("User not found. Contact PAYCOMET");
            case 1123:
                return __("Invalid signature. Please check your configuration");
            case 1124:
                return __("Operation not available with the specified user");
            case 1125:
                return __("Invalid operation in a currency other than Euro");
            case 1127:
                return __("Quantity zero or invalid");
            case 1128:
                return __("Current currency conversion invalid");
            case 1129:
                return __("Invalid amount");
            case 1130:
                return __("Product not found");
            case 1131:
                return __("Invalid operation with the current currency");
            case 1132:
                return __("Invalid operation with a different article of the Euro currency");
            case 1133:
                return __("Info button corrupt");
            case 1134:
                return __("The subscription may not exceed the expiration date of the card");
            case 1135:
                return __("DS_EXECUTE can not be true if DS_SUBSCRIPTION_STARTDATE is different from today.");
            case 1136:
                return __("PAYCOMET_OPERATIONS_MERCHANTCODE field error");
            case 1137:
                return __("PAYCOMET_OPERATIONS_TERMINAL must be Array");
            case 1138:
                return __("PAYCOMET_OPERATIONS_OPERATIONS must be Array");
            case 1139:
                return __("PAYCOMET_OPERATIONS_SIGNATURE field error");
            case 1140:
                return __("Can not find any of the PAYCOMET_OPERATIONS_TERMINAL");
            case 1141:
                return __("Error in the date range requested");
            case 1142:
                return __("The application can not have a length greater than 2 years");
            case 1143:
                return __("The operation state is incorrect");
            case 1144:
                return __("Error in the amounts of the search");
            case 1145:
                return __("The type of operation requested does not exist");
            case 1146:
                return __("Sort Order unrecognized");
            case 1147:
                return __("PAYCOMET_OPERATIONS_SORTORDER unrecognized");
            case 1148:
                return __("Subscription start date wrong");
            case 1149:
                return __("Subscription end date wrong");
            case 1150:
                return __("Frequency error in the subscription");
            case 1151:
                return __("Invalid usuarioXML");
            case 1152:
                return __("Invalid codigoCliente");
            case 1153:
                return __("Invalid usuarios parameter");
            case 1154:
                return __("Invalid firma parameter");
            case 1155:
                return __("Invalid usuarios parameter format");
            case 1156:
                return __("Invalid type");
            case 1157:
                return __("Invalid name");
            case 1158:
                return __("Invalid surname");
            case 1159:
                return __("Invalid email");
            case 1160:
                return __("Invalid password");
            case 1161:
                return __("Invalid language");
            case 1162:
                return __("Invalid maxamount");
            case 1163:
                return __("Invalid multicurrency");
            case 1165:
                return __("Invalid permissions_specs. Format not allowed");
            case 1166:
                return __("Invalid permissions_products. Format not allowed");
            case 1167:
                return __("Invalid email. Format not allowed");
            case 1168:
                return __("Weak or invalid password");
            case 1169:
                return __("Invalid value for type parameter");
            case 1170:
                return __("Invalid value for language parameter");
            case 1171:
                return __("Invalid format for maxamount parameter");
            case 1172:
                return __("Invalid multicurrency. Format not allowed");
            case 1173:
                return __("Invalid permission_id – permissions_specs. Not allowed");
            case 1174:
                return __("Invalid user");
            case 1175:
                return __("Invalid credentials");
            case 1176:
                return __("Account not found");
            case 1177:
                return __("User not found");
            case 1178:
                return __("Invalid signature");
            case 1179:
                return __("Account without products");
            case 1180:
                return __("Invalid product_id - permissions_products. Not allowed");
            case 1181:
                return __("Invalid permission_id -permissions_products. Not allowed");
            case 1185:
                return __("Minimun limit not allowed");
            case 1186:
                return __("Maximun limit not allowed");
            case 1187:
                return __("Daily limit not allowed");
            case 1188:
                return __("Monthly limit not allowed");
            case 1189:
                return __("Max amount (same card / last 24 h.) not allowed");
            case 1190:
                return __("Max amount (same card / last 24 h. / same IP address) not allowed");
            case 1191:
                return __("Day / IP address limit (all cards) not allowed");
            case 1192:
                return __("Country (merchant IP address) not allowed");
            case 1193:
                return __("Card type (credit / debit) not allowed");
            case 1194:
                return __("Card brand not allowed");
            case 1195:
                return __("Card Category not allowed");
            case 1196:
                return __("Authorization from different country than card issuer, not allowed");
            case 1197:
                return __("Denied. Filter: Card country issuer not allowed");
            case 1198:
                return __("Scoring limit exceeded");
            case 1200:
                return __("Denied. Filter: same card, different country last 24 h.");
            case 1201:
                return __("Number of erroneous consecutive attempts with the same card exceeded");
            case 1202:
                return __("Number of failed attempts (last 30 minutes) from the same ip address exceeded");
            case 1203:
                return __("Wrong or not configured PayPal credentials");
            case 1204:
                return __("Wrong token received");
            case 1205:
                return __("Can not perform the operation");
            case 1206:
                return __("ProviderID not available");
            case 1207:
                return __("Operations parameter missing or not in a correct format");
            case 1208:
                return __("PaycometMerchant parameter missing");
            case 1209:
                return __("MerchatID parameter missing");
            case 1210:
                return __("TerminalID parameter missing");
            case 1211:
                return __("TpvID parameter missing");
            case 1212:
                return __("OperationType parameter missing");
            case 1213:
                return __("OperationResult parameter missing");
            case 1214:
                return __("OperationAmount parameter missing");
            case 1215:
                return __("OperationCurrency parameter missing");
            case 1216:
                return __("OperationDatetime parameter missing");
            case 1217:
                return __("OriginalAmount parameter missing");
            case 1218:
                return __("Pan parameter missing");
            case 1219:
                return __("ExpiryDate parameter missing");
            case 1220:
                return __("Reference parameter missing");
            case 1221:
                return __("Signature parameter missing");
            case 1222:
                return __("OriginalIP parameter missing or not in a correct format");
            case 1223:
                return __("Authcode / errorCode parameter missing");
            case 1224:
                return __("Product of the operation missing");
            case 1225:
                return __("The type of operation is not supported");
            case 1226:
                return __("The result of the operation is not supported");
            case 1227:
                return __("The transaction currency is not supported");
            case 1228:
                return __("The date of the transaction is not in a correct format");
            case 1229:
                return __("The signature is not correct");
            case 1230:
                return __("Can not find the associated account information");
            case 1231:
                return __("Can not find the associated product information");
            case 1232:
                return __("Can not find the associated user information");
            case 1233:
                return __("The product is not set as multicurrency");
            case 1234:
                return __("The amount of the transaction is not in a correct format");
            case 1235:
                return __("The original amount of the transaction is not in a correct format");
            case 1236:
                return __("The card does not have the correct format");
            case 1237:
                return __("The expiry date of the card is not in a correct format");
            case 1238:
                return __("Can not initialize the service");
            case 1239:
                return __("Can not initialize the service");
            case 1240:
                return __("Method not implemented");
            case 1241:
                return __("Can not initialize the service");
            case 1242:
                return __("Service can not be completed");
            case 1243:
                return __("OperationCode parameter missing");
            case 1244:
                return __("bankName parameter missing");
            case 1245:
                return __("csb parameter missing");
            case 1246:
                return __("userReference parameter missing");
            case 1247:
                return __("Can not find the associated FUC");
            case 1248:
                return __("Duplicate xref. Pending operation.");
            case 1249:
                return __("[DS_]AGENT_FEE parameter missing");
            case 1250:
                return __("[DS_]AGENT_FEE parameter is not in a correct format");
            case 1251:
                return __("DS_AGENT_FEE parameter is not correct");
            case 1252:
                return __("CANCEL_URL parameter missing");
            case 1253:
                return __("CANCEL_URL parameter is not in a correct format");
            case 1254:
                return __("Commerce with secure cardholder and cardholder without secure purchase key");
            case 1255:
                return __("Call terminated by the client");
            case 1256:
                return __("Call terminated, incorrect attempts exceeded");
            case 1257:
                return __("Call terminated, operation attempts exceeded");
            case 1258:
                return __("stationID not available");
            case 1259:
                return __("It has not been possible to establish the IVR session");
            case 1260:
                return __("merchantCode parameter missing");
            case 1261:
                return __("The merchantCode parameter is incorrect");
            case 1262:
                return __("terminalIDDebtor parameter missing");
            case 1263:
                return __("terminalIDCreditor parameter missing");
            case 1264:
                return __("Authorisations for carrying out the operation not available");
            case 1265:
                return __("The Iban account (terminalIDDebtor) is invalid");
            case 1266:
                return __("The Iban account (terminalIDCreditor) is invalid");
            case 1267:
                return __("The BicCode of the Iban account (terminalIDDebtor) is invalid");
            case 1268:
                return __("The BicCode of the Iban account (terminalIDCreditor) is invalid");
            case 1269:
                return __("operationOrder parameter missing");
            case 1270:
                return __("The operationOrder parameter does not have the correct format");
            case 1271:
                return __("The operationAmount parameter does not have the correct format");
            case 1272:
                return __("The operationDatetime parameter does not have the correct format");
            case 1273:
                return __("The operationConcept parameter contains invalid characters or exceeds 140 characters");
            case 1274:
                return __("It has not been possible to record the SEPA operation");
            case 1275:
                return __("It has not been possible to record the SEPA operation");
            case 1276:
                return __("Can not create an operation token");
            case 1277:
                return __("Invalid scoring value");
            case 1278:
                return __("The language parameter is not in a correct format");
            case 1279:
                return __("The cardholder name is not in a correct format");
            case 1280:
                return __("The card does not have the correct format");
            case 1281:
                return __("The month does not have the correct format");
            case 1282:
                return __("The year does not have the correct format");
            case 1283:
                return __("The cvc2 does not have the correct format");
            case 1284:
                return __("The JETID parameter is not in a correct format");
            case 1288:
                return __("The splitId parameter is not valid");
            case 1289:
                return __("The splitId parameter is not allowed");
            case 1290:
                return __("This terminal don't allow split transfers");
            case 1291:
                return __("It has not been possible to record the split transfer operation");
            case 1292:
                return __("Original payment's date cannot exceed 90 days");
            case 1293:
                return __("Original split tansfer not found");
            case 1294:
                return __("Total reversal cannot exceed original split transfer");
            case 1295:
                return __("It has not been possible to record the split transfer reversal operation");
        }
    }
}
