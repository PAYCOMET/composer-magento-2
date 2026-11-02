<?php

namespace Paycomet\Payment\Controller\Cards;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Paycomet\Payment\Helper\Data;

class Add extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var ResourceConnection
     */
    private $_resourceConnection;

    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param Data $helper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Data $helper,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_helper = $helper;
        $this->_resourceConnection = $resourceConnection;
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

        if (!$this->getRequest()->isPost()) {
            return $this->_redirect('paycomet_payment/cards/view');
        }

        $params = $this->getRequest()->getParams();
        $paytpvToken = $params['paytpvToken'] ?? null;
        if ($paytpvToken) {
            try {
                $this->_helper->addUserToken($paytpvToken);
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('jetToken card failed'),
                    'PAYCOMET_messages'
                );
            }
        }

        return $this->_redirect('paycomet_payment/cards/view');
    }

    /**
     * Delete Paycomet Card
     *
     * @param string $hash
     * @param int $customer_id
     */
    public function deletePaycometCard(string $hash, int $customerId)
    {
        $connection = $this->_resourceConnection->getConnection();
        $tableName = $this->_resourceConnection->getTableName('paycomet_token');

        $where = [
            'hash = ?' => $hash,
            'customer_id = ?' => (int)$customerId,
        ];

        return $connection->delete($tableName, $where);

    }
}
