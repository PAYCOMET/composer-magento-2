<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paycomet\Payment\Model\Config\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class ApplePayButtonColor implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * To Option array
     */
    public function toOptionArray()
    {
        return [
            "black" => __('Black'),
            "white" => __('White'),
            "white-outline" => __('White outline')
        ];
    }
}
