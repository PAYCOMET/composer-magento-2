<?php

namespace Paycomet\Payment\Helper\Apm\Instantcredit;

use Magento\Framework\App\Helper\AbstractHelper;

class IcHelper extends AbstractHelper
{
    /**
     * Get if payment method is enabled from Instant Credit config
     *
     * @return mixed
     */
    public function getIsEnabled()
    {
        return $this->scopeConfig->getValue(
            'payment/paycomet_instantcredit/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get upper limit to show payment method from Instant Credit config
     *
     * @return float
     */
    public function getUpperLimit()
    {
        return floatval(
            $this->scopeConfig->getValue(
                'payment/paycomet_instantcredit/upper_limit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Get lower limit to show payment method from Instant Credit config
     *
     * @return float
     */
    public function getLowerLimit()
    {
        return floatval(
            $this->scopeConfig->getValue(
                'payment/paycomet_instantcredit/lower_limit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Check if price passed by parameter is between limits
     *
     * @param string $price
     * @return bool
     */
    public function isBetweenLimits($price)
    {
        $price = floatval($price);
        $upper = $this->getUpperLimit();
        $lower = $this->getLowerLimit();

        if ($upper == 0 && $lower == 0) {
            return true;
        }

        if ($lower > 0 && $price < $lower) {
            return false;
        }

        if ($upper > 0 && $price > $upper) {
            return false;
        }

        return true;
    }
}
