<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paytpv\Payment\Model\Config\Source;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 */
class PaymentAction implements ArrayInterface
{
    /**
     * Possible actions on order place
     * 
     * @return array
     */


    const AUTHORIZE = AbstractMethod::ACTION_AUTHORIZE;
    const AUTHORIZE_CAPTURE = AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
    


    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize') . " (" . __("Pre-Authorization") . ")",
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture') . " (" . __("Sale") . ")",
            ]
        ];
    }
}
