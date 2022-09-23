<?php

namespace Paycomet\Payment\ViewModel\Apm\Instantcredit;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

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

    private const SIMULATOR_URL       = "https://instantcredit.net/simulator/ic-simulator.js";
    private const SIMULATOR_URL_TEST  = "https://instantcredit.net/simulator/test/ic-simulator.js";

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
     * Get IcHelper
     *
     * @return \Paycomet\Payment\Helper\Apm\Instantcredit\IcHelper
     */
    public function getIcHelper()
    {
        return $this->icHelper;
    }

    /**
     * Get if odd simulator can be showed in cart
     *
     * @return mixed
     */
    public function showInCart()
    {
        return $this->scopeConfig->getValue(
            'payment/paycomet_instantcredit/show_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get hash to use in odd simulator
     *
     * @return mixed
     */
    public function getHash()
    {
        return $this->scopeConfig->getValue(
            'payment/paycomet_instantcredit/hash',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get grand total from current quote
     *
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
     * Get Price Formatted
     *
     * @param string $price
     * @return mixed|string
     */
    public function getPriceFormatted($price)
    {
        if ($price == (int)$price) {
            return $price . ',00';
        }

        return str_replace('.', ',', (string) $price);
    }

    /**
     * Get current product
     *
     * @return mixed
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get IC simulator URL
     *
     * @return return
     */
    public function getSimulatorUrl()
    {
        if ($this->scopeConfig->getValue(
            'payment/paycomet_instantcredit/simulatorenvironment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == 1
        ) {
            return self::SIMULATOR_URL_TEST;
        } else {
            return self::SIMULATOR_URL;
        }
    }
}
