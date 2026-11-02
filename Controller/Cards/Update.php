<?php

namespace Paycomet\Payment\Controller\Cards;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

class Update extends Action
{
    /**
     * @var CustomerSession
     */
    private $_customerSession;

    /**
     * @var ResourceConnection
     */
    private $_resourceConnection;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
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
        $hash = $params['item'] ?? null;
        $cardDesc = $params['card_desc'] ?? null;
        $customerId = $this->_customerSession->getCustomerId();

        if ($hash && $customerId && $cardDesc !== null) {
            try {
                $this->updatePaycometCard($hash, $customerId, $cardDesc);
                $this->messageManager->addSuccessMessage(__('Tarjeta actualizada correctamente.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error al actualizar la tarjeta'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Faltan parámetros requeridos para la actualización.'));
        }

        return $this->_redirect('paycomet_payment/cards/view');
    }

    /**
     * Update Paycoment Card
     *
     * @param string $hash
     * @param int $customer_id
     * @param string $card_desc
     */
    public function updatePaycometCard(string $hash, int $customerId, string $cardDesc): int
    {

        $connection = $this->_resourceConnection->getConnection();
        $tableName = $this->_resourceConnection->getTableName('paycomet_token');

        $data = ['desc' => $cardDesc];
        $where = [
            'hash = ?' => $hash,
            'customer_id = ?' => (int)$customerId,
        ];

        return $connection->update($tableName, $data, $where);
    }

    /**
     * Update Paycoment Card Expiry Date
     *
     * @param string $hash
     * @param int $customerId
     * @param string $expiry
     */
    public function updatePaycometCardExpiryDate(string $hash, int $customerId, string $expiry): int
    {
        $connection = $this->_resourceConnection->getConnection();
        $tableName = $this->_resourceConnection->getTableName('paycomet_token');

        $data = ['expiry' => $expiry];
        $where = [
            'hash = ?' => $hash,
            'customer_id = ?' => (int)$customerId,
        ];

        return $connection->update($tableName, $data, $where);
    }
}
