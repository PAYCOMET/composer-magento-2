<?php

namespace Paytpv\Payment\Block\Process\Result;

class Messages extends \Magento\Framework\View\Element\Messages
{
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $messages = $this->messageManager->getMessages(true, 'PAYTPV_messages');
        $this->addMessages($messages);

        return parent::_prepareLayout();
    }
}
