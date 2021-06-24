<?php

namespace Paycomet\Payment\ViewModel\Apm\Instantcredit;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class OddSimulatorViewModel
 * @package Paycomet\Payment\ViewModel\Apm\Instantcredit;
  */
class OddSimulatorViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $helperCatalog;
    /**
     * @var \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper
     */
    private $icHelper;

    /**
     * OddSimulatorViewModel constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Helper\Data $helperCatalog
     * @param \Magento\Framework\Registry $registry
     * @param \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper $icHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Helper\Data $helperCatalog,
        \Magento\Framework\Registry $registry,
        \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper $icHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helperCatalog = $helperCatalog;
        $this->registry = $registry;
        $this->icHelper = $icHelper;
    }

    /**
     * @return \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper
     */
    public function getIcHelper()
    {
        return $this->icHelper;
    }

    /**
     * Get if odd simulator can be showed in cart
     * @return mixed
     */
    public function showInCart()
    {
        return $this->scopeConfig->getValue('payment/paycomet_instantcredit/show_cart');
    }

    /**
     * Get hash to use in odd simulator
     * @return mixed
     */
    public function getHash()
    {
        return $this->scopeConfig->getValue('payment/paycomet_instantcredit/hash');
    }

    /**
     * Get grand total from current quote
     * @return float
     */
    public function getQuoteGrandTotal()
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException | LocalizedException $e) {
            return 0.0;
        }

        return $quote->getGrandTotal();

    }


    /**
     * @param $price
     * @return mixed|string
     */
    public function getPriceFormatted($price)
    {
        if ($price == intval($price)) {
            return $price . ',00';
        }

        return str_replace('.', ',', (string) $price);

    }

    /**
     * Get current product
     * @return mixed
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

}