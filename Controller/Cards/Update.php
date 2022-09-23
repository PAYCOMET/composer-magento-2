<?php

namespace Paycomet\Payment\Controller\Cards;

use Magento\Framework\App\RequestInterface;

class Update extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
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
        $hash = $response["item"];
        $customer_id = $this->_customerSession->getCustomerId();
        $card_desc = $response["card_desc"];

        $this->updatePaycometCard($hash, $customer_id, $card_desc);

        $this->_redirect('paycomet_payment/cards/view');
    }

    /**
     * Update Paycoment Card
     *
     * @param string $hash
     * @param int $customer_id
     * @param string $card_desc
     */
    public function updatePaycometCard($hash, $customer_id, $card_desc)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('\Magento\Framework\App\ResourceConnection::class');
        $connection = $resource->getConnection();

        $data = [
            "desc"=>$card_desc
        ];
        $conds[] = $connection->quoteInto("hash" . ' = ?', $hash);
        $conds[] = $connection->quoteInto("customer_id" . ' = ?', $customer_id);

        $where = implode(' AND ', $conds);

        $connection->update($resource->getTableName('paycomet_token'), $data, $where);
    }
}
