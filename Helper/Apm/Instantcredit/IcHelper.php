<?php

namespace Paycomet\Payment\Helper\Apm\Instantcredit;

use Magento\Framework\App\Helper\AbstractHelper;


class IcHelper extends AbstractHelper
{
    /**
     * Get if payment method is enabled from Instant Credit config
     * @return mixed
     */
    public function getIsEnabled()
    {
        return $this->scopeConfig->getValue('payment/paycomet_instantcredit/active');
    }


   /**
     * Get upper limit to show payment method from Instant Credit config
     * @return float
     */
    public function getUpperLimit()
    {
        return floatval($this->scopeConfig->getValue('payment/paycomet_instantcredit/upper_limit'));
    }

    /**
     * Get lower limit to show payment method from Instant Credit config
     * @return float
     */
    public function getLowerLimit()
    {
        return floatval($this->scopeConfig->getValue('payment/paycomet_instantcredit/lower_limit'));
    }

    /**
     * Check if price passed by parameter is between limits
     * @param $price
     * @return bool
     */
    public function isBetweenLimits($price)
    {
        $price = floatval($price);
        $upper = $this->getUpperLimit();
        $lower = $this->getLowerLimit();

        if ( $upper == 0 && $lower == 0) {
            return true;
        }
        return ($price >= $lower && $price <= $upper);
    }
}