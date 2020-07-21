<?php

namespace Paycomet\Payment\Model\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class PaycometConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PaymentHelper
     */
    private $_paymentHelper;

    /**
     * @var \Paycomet\Payment\Helper\Data
     */
    private $_helper;

    /**
     * @var string[]
     */
    protected $_methodCodes = [
        'paycomet_payment',
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    private $methods = [];

     /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;


    /**
     * PaycometConfigProvider constructor.
     *
     * @param PaymentHelper                   $paymentHelper
     * @param \Paycomet\Payment\Helper\Data     $helper
     * @param \Magento\Framework\Escaper    $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \Paycomet\Payment\Helper\Data $helper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_helper = $helper;
        $this->_escaper = $escaper;

        foreach ($this->_methodCodes as $code) {
            $this->methods[$code] = $this->_paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * Set configuration for Paycomet Payment.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'paycomet_payment' => [
                ],
            ],
        ];

        foreach ($this->_methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment'] [$code]['redirectUrl'] = $this->getMethodRedirectUrl($code);
                $config['payment'] [$code]['iframeEnabled'] = $this->_helper->getConfigData('iframe_enabled');
                $config['payment'] [$code]['iframeMode'] = $this->_helper->getConfigData('iframe_mode');
                $config['payment'] [$code]['iframeHeight'] = $this->_helper->getConfigData('iframe_height');
                $config['payment'] [$code]['card_offer_save'] = ($this->_helper->getConfigData('card_offer_save') && $this->_helper->getCustomerId());
                $config['payment'] [$code]['paycometCards'] = $this->getPaycometToken($code);
                $config['payment'] [$code]['form_footer'] = nl2br($this->_escaper->escapeHtml($this->_helper->getConfigData('form_footer')));
            }
        }


        return $config;
    }

    /**
     * Return redirect URL for method.
     *
     * @param string $code
     *
     * @return mixed
     */
    private function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }


    /**
     * Return redirect URL for method.
     *
     * @param string $code
     *
     * @return mixed
     */
    private function getPaycometToken($code)
    {
        return $this->methods[$code]->getCheckoutCards();
    }
}
