<?php

namespace Paytpv\Payment\Controller\Cards;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * Redirect to cards.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('My Stored Cards PAYTPV'));
        $navigationBlock = $this->_view->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('paytpv_payment/cards/view');
        }
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
