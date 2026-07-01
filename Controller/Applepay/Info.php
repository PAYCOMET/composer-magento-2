<?php

namespace Paycomet\Payment\Controller\Applepay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Paycomet\Payment\Model\Apm\Applepay\PaymentMethod;

class Info extends Action
{

    protected $resultJsonFactory;
    protected $paymentMethod;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PaymentMethod $paymentMethod
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentMethod = $paymentMethod;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $applePayButtonInfo = $this->paymentMethod->getApplePayButtonInfo();
            $data = json_decode(json_encode($applePayButtonInfo), true);

            if (!is_array($data)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Applepay buttonInfo failed'));
            }

            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
