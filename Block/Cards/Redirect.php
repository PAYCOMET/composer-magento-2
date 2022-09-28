<?php

namespace Paycomet\Payment\Block\Cards;

class Redirect extends \Magento\Payment\Block\Form
{
    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * Redirect constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param \Paycomet\Payment\Helper\Data                     $helper
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Paycomet\Payment\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * Get Form Paycomet Url
     *
     * @return string
     */
    public function getFormPaycometUrl()
    {
        $result = '';
        try {
            $result = $this->_helper->getPaycometAddUserUrl();
        } catch (\Exception $e) {
            // do nothing for now
            $this->_helper->logDebug($e);
            throw($e);
        }

        return $result;
    }
}
