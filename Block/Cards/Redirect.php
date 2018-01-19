<?php

namespace Paytpv\Payment\Block\Cards;

class Redirect extends \Magento\Payment\Block\Form
{
    /**
     * @var \Paytpv\Payment\Helper\Data
     */
    private $_helper;

    /**
     * Redirect constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Paytpv\Payment\Helper\Data                  $helper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Paytpv\Payment\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * @return string
     */
    public function getFormPaytpvUrl()
    {
        $result = '';
        try {
            $result = $this->_helper->getPaytpvAddUserUrl();
        } catch (\Exception $e) {
            // do nothing for now
            $this->_helper->logDebug($e);
            throw($e);
        }

        return $result;
    }
}
