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
class ICSimulator implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
        	0 => __('Production'),
        	1 => __('Test')
        ];
    }
}
