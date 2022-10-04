<?php

namespace Paycomet\Payment\Block\Process\Result;

class Messages extends \Magento\Framework\View\Element\Messages
{
    /**
     * Prepare Layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $messages = $this->messageManager->getMessages(true, 'PAYCOMET_messages');
        $this->addMessages($messages);

        return parent::_prepareLayout();
    }
}
