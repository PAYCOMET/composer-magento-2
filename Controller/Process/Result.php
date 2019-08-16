<?php

namespace Paycomet\Payment\Controller\Process;

class Result extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry\Registry
     */
    private $coreRegistry;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    private $_logger;

    /**
     * @var \Paycomet\Payment\Api\PaycometPaymentManagementInterface
     */
    private $_paymentManagement;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Paycomet\Payment\Helper\Data                          $helper
     * @param \Magento\Sales\Model\OrderFactory                    $orderFactory
     * @param \Magento\Framework\Registry                          $coreRegistry
     * @param \Paycomet\Payment\Logger\Logger                        $logger
     * @param \Paycomet\Payment\Api\PaycometPaymentManagementInterface $paymentManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paycomet\Payment\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Paycomet\Payment\Logger\Logger $logger,
        \Paycomet\Payment\Api\PaycometPaymentManagementInterface $paymentManagement
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_url = $context->getUrl();
        $this->coreRegistry = $coreRegistry;
        $this->_logger = $logger;
        $this->_paymentManagement = $paymentManagement;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try{
            $response = $this->getRequest()->getParams();
            unset($response["TransactionName"]);

            if ($response) {
                //the default
                //$params['returnUrl'] = $this->_url->getUrl('checkout/cart');

                // Notification
                if (isset($response["TransactionType"])){

                    // Process Notification
                    $result = $this->_handleResponse($response);

                // URL OK,KO
                }else{
                    $sessionParams = $response;
                    // Process URL OK/KO
                    $params['returnUrl'] = $this->_url->getUrl('paycomet_payment/process/sessionresult', $sessionParams);
                    $this->coreRegistry->register(\Paycomet\Payment\Block\Process\Result::REGISTRY_KEY, $params);

                    $this->_view->loadLayout();
                    $this->_view->getLayout()->initMessages();
                    $this->_view->renderLayout();
                }

            }
        }catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function _handleResponse($response)
    {
        if (empty($response)) {
            $this->_logger->critical(__('Empty response received from gateway'));

            return false;
        }


        $this->_helper->logDebug(__('Gateway response:').print_r($this->_helper->stripTrimFields($response), true));

        // validate response
        $authStatus = $this->_validateResponse($response);
        if (!$authStatus) {

            $this->_logger->critical(__('Invalid response received from gateway.'));
            
            return false;
        }

        
        $transaction_type = $response['TransactionType'];
        switch ($transaction_type){
            // add_user
            case 107:               
                // process the response
                return $this->_paymentManagement->processResponseAddUser($response);
                                
                break;

            default:

                //get the actual order id
                $incrementId = $response['Order'];

                if ($incrementId) {
                    $order = $this->_getOrder($incrementId);
                    if ($order->getId()) {                        
                        // process the response
                        return $this->_paymentManagement->processResponse($order, $response);
                    } else {
                        $this->_logger->critical(__('Gateway response has an invalid order id.'));

                        return false;
                    }
                } else {
                    $this->_logger->critical(__('Gateway response does not have an order id.'));

                    return false;
                }

                break;
        }
        
    }

    /**
     * Validate response using sha1 signature.
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponse($response)
    {

        $transaction_type = $response['TransactionType'];

        $ref = $response['Order'];
        $resp = $response['Response'];


        switch ($transaction_type){
            case 107: // add_user

                $arrDatos = explode("|",$ref);
                $storeId = $arrDatos[1];

                $merchant_code = $this->_helper->getConfigData('merchant_code',$storeId);
                $merchant_terminal = $this->_helper->getConfigData('merchant_terminal',$storeId);
                $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass',$storeId);

                $sign = $response['NotificationHash'];
                $datetime = $response['DateTime'];
                $local_sign = hash('sha512',$merchant_code.$merchant_terminal.$transaction_type.$ref.$datetime. md5($merchant_pass));

                break;

            default:
                $order = $this->_getOrder($response['Order']);
                $storeId = $order->getStoreId();

                $merchant_code = $this->_helper->getConfigData('merchant_code',$storeId);
                $merchant_terminal = $this->_helper->getConfigData('merchant_terminal',$storeId);
                $merchant_pass = $this->_helper->getEncryptedConfigData('merchant_pass',$storeId);     
            
                $sign = $response['NotificationHash'];
                $amount = $response['Amount'];
                $currency = $response['Currency'];
                $bankdatetime = $response['BankDateTime'];

                $local_sign = hash('sha512',$merchant_code.$merchant_terminal.$transaction_type.$ref.$amount.$currency.md5($merchant_pass).$bankdatetime.$resp);

                break;
            
        } 
        
       
        //Check to see if hashes match or not
        if (strcmp($sign, $local_sign) != 0) {            
            return false;
        }

        return true;
    }

    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     *
     * @return array
     */
    private function _buildSessionParams($result){
        $result = ($result) ? '1' : '0';
        $timestamp = strftime('%Y%m%d%H%M%S');
        $merchant_code = $this->_helper->getConfigData('merchant_code');
        $orderid = $this->_order->getIncrementId();
        $sha1hash = $this->_helper->signFields("$timestamp.$merchant_code.$orderid.$result");

        return ['timestamp' => $timestamp, 'order_id' => $orderid, 'result' => $result, 'hash' => $sha1hash];
    }

    /**
     * Get order based on increment id.
     *
     * @param $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder($incrementId)
    {
        if (!$this->_order) {
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }
}
