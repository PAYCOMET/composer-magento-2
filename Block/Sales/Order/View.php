<?php
namespace Paycomet\Payment\Block\Sales\Order;

class View extends \Magento\Framework\View\Element\Template
{

    private $_code;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\View\Page\Config $pageConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->pageConfig = $pageConfig;

        $this->loadInfo();

    }


    public function loadInfo()
    {
        
        $orderId = $this->getRequest()->getParam('order_id');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($orderId);

        $this->_code = $order->getPayment()->getMethodInstance()->getCode();

        if ($this->_code == 'paycomet_multibanco') {
           
            $methodData = $order->getPayment()->getAdditionalInformation('METHOD_DATA');
            $methodData = json_decode($methodData);

            $this->addData(
                [
                'code' => $order->getPayment()->getMethodInstance()->getCode(),
                'title' => $order->getPayment()->getMethodInstance()->getTitle(),
                'img_src' => $this->getViewFileUrl('Paycomet_Payment::img/apms/'.$this->_code . '.svg'),
                'Entity' => $methodData->entityNumber,
                'Reference' => $methodData->referenceNumber
                ]
            );

        }
    }

}