<?php

namespace Paycomet\Payment\Block\Process;

class Result extends \Magento\Framework\View\Element\Template
{
    public const REGISTRY_KEY = 'paycomet_payment_params';

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * Process constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry                      $registry
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Prepare Layout
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        $params = $this->coreRegistry->registry(self::REGISTRY_KEY);
        $this->setParams($params);

        return parent::_prepareLayout();
    }
}
