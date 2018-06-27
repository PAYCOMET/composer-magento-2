<?php

namespace Paytpv\Payment\Api;

/**
 * Interface PaytpvPaymentManagementInterface.
 *
 * @api
 */
interface PaytpvPaymentManagementInterface
{
    /**
     * Processes the payment response from the gateway.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array                      $response
     *
     * @return bool
     */
    public function processResponse($order, $response);

    /**
     * Restore cart.
     *
     * @param string $cartId
     *
     * @return mixed
     */
    public function restoreCart($cartId);
}
