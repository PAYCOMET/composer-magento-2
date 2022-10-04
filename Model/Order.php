<?php

namespace Paycomet\Payment\Model;

use Magento\Sales\Model\Order as parentOrder;

class Order extends parentOrder
{
    /**
     * Hold
     *
     * @return $this
     */
    public function hold()
    {
        $method = $this->getPayment()->getMethodInstance();
        if ($method->getCode() == 'paycomet_payment') {
            $method->hold($this->getPayment());
        }
        return parent::hold();
    }

    /**
     * Unhold
     *
     * @return $this
     */
    public function unhold()
    {
        $method = $this->getPayment()->getMethodInstance();

        if ($method->getCode() == 'paycomet_payment') {
            $method->acceptPayment($this->getPayment());
        }

        return parent::unhold();
    }
}
