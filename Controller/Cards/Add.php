<?php

namespace Paycomet\Payment\Controller\Cards;

use Magento\Framework\App\RequestInterface;

class Add extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $customerSession
     * @param \Paycomet\Payment\Helper\Data         $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Paycomet\Payment\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_helper = $helper;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$request->isDispatched()) {
            return parent::dispatch($request);
        }
        if (!$this->_customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }

        return parent::dispatch($request);
    }

    /**
     * View cards.
     */
    public function execute()
    {
        $response = $this->getRequest()->getParams();


        if ($response["paytpvToken"]) {
            try {
                $this->_helper->addUserToken($response["paytpvToken"]);
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('jetToken card failed'),
                    'PAYCOMET_messages'
                );
            }
        }

        $this->_redirect('paycomet_payment/cards/view');
    }

    /**
     * Delete Paycomet Card
     *
     * @param string $hash
     * @param int $customer_id
     */
    public function deletePaycometCard($hash, $customer_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();

        $conds[] = $connection->quoteInto("hash" . ' = ?', $hash);
        $conds[] = $connection->quoteInto("customer_id" . ' = ?', $customer_id);

        $where = implode(' AND ', $conds);

        return $connection->delete($resource->getTableName('paycomet_token'), $where);
    }
}
