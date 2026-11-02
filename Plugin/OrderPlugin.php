<?php
namespace Paycomet\Payment\Plugin;

use Magento\Sales\Model\Order;

class OrderPlugin
{
    /**
     * Intercepta antes de ejecutar $order->hold()
     *
     * @param Order $subject
     * @return void
     */
    public function beforeHold(Order $subject)
    {
        $payment = $subject->getPayment();
        if ($payment) {
            $method = $payment->getMethodInstance();
            if ($method && $method->getCode() === 'paycomet_payment') {
                $method->hold($payment);
            }
        }
    }

    /**
     * Intercepta antes de ejecutar $order->unhold()
     *
     * @param Order $subject
     * @return void
     */
    public function beforeUnhold(Order $subject)
    {
        $payment = $subject->getPayment();
        if ($payment) {
            $method = $payment->getMethodInstance();
            if ($method && $method->getCode() === 'paycomet_payment') {
                $method->acceptPayment($payment);
            }
        }
    }
}
