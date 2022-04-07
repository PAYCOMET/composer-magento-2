<?php

namespace Paycomet\Payment\Model\Api;

use Paycomet\Payment\Model\Config\Source\PaymentAction;

use Paycomet\Payment\Observer\DataAssignObserver;

class PaycometPaymentManagement implements \Paycomet\Payment\Api\PaycometPaymentManagementInterface
{
    const FRAUD_ACTIVE = 'ACTIVE';
    const FRAUD_HOLD = 'HOLD';
    const FRAUD_BLOCK = 'BLOCK';
    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var \Paycomet\Payment\Logger\Logger
     */
    private $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    private $_orderHistoryFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * PaycometManagement constructor.
     *
     * @param \Paycomet\Payment\Helper\Data                                 $helper
     * @param \Magento\Checkout\Model\Session                                 $session
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Paycomet\Payment\Logger\Logger                               $logger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender             $orderSender
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory                $orderHistoryFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface               $customerRepository
     */
    public function __construct(
        \Paycomet\Payment\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Paycomet\Payment\Logger\Logger $logger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->_helper = $helper;
        $this->_session = $session;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_logger = $logger;
        $this->_orderSender = $orderSender;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function processResponse($order, $response)
    {
        $payment = $order->getPayment();
        if (!$this->_validateResponseFields($response)) {
            try {
                $this->_helper->setAdditionalInfo($payment, $response);
                $order->save();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }

            return false;
        }

        // Verificamos que no se haya creado ya la misma Transacción.
        if ( $payment->getLastTransId() == $response["AuthCode"]) {
            print "PAYCOMET 1";
            return false;
        }

        $this->_helper->CreateTransInvoice($order,$response);

        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function processResponseAddUser($response)
    {

        if (!$this->_validateResponseFieldsAddUser($response)) {
            return false;
        }

        //Store customer card
        $datosOrder = explode("_",$response["Order"]);
        $customerId = $datosOrder[0];
	    $storeId = $datosOrder[1];

        if (!empty($customerId)) {
            return $this->_helper->_handleCardStorage($response, $customerId, $storeId);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restoreCart($cartId)
    {
        $session = $this->_session;
        $order = $session->getLastRealOrder();
        if ($order->getId()) {
            // restore the quote
            if ($session->restoreQuote()) {
                $this->_helper->cancelOrder($order);
            }
        }
    }


    /**
     * @desc Validates the response fields
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponseFields($response)
    {
        if ($response == null ||
           !isset($response['Response']) ||
           !isset($response['Amount']) ||
           $response['Response'] != 'OK' ||
           !ctype_digit($response['Amount'])) {
            return false;
        }

        return true;
    }


    /**
     * @desc Validates the response fields add_user
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponseFieldsAddUser($response)
    {
        if ($response == null ||
           !isset($response['Order']) ||
           !isset($response['Response']) ||
           $response['Response'] != 'OK') {
            return false;
        }

        return true;
    }

}
