<?php
namespace Paycomet\Payment\Block\Apm\Instantcredit\Element;

/**
 * Class Template
 *
 * Class to check if module is active before render it
 */
class Template extends \Magento\Framework\View\Element\Template
{
    /**
     * @inheritdoc
     */
    public function _toHtml()
    {
        if (!$this->_scopeConfig->isSetFlag('payment/paycomet_instantcredit/active')) {
            return '';
        }
        return parent::_toHtml();
    }
}
