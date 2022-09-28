<?php

namespace Paycomet\Payment\Logger\Handler;

use Monolog\Logger;

class Critical extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level.
     *
     * @var int
     */
    protected $loggerType = Logger::CRITICAL;

    /**
     *
     * @var string
     */
    protected $fileName = '/var/log/paycomet/error.log';
}
