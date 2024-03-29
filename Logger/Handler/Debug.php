<?php

namespace Paycomet\Payment\Logger\Handler;

use Monolog\Logger;

class Debug extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level.
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     *
     * @var string
     */
    protected $fileName = '/var/log/paycomet/debug.log';
}
