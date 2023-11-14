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
    // APMs
    protected $_methodCodes = [
        'paycomet_payment',
        'paycomet_bizum',
        'paycomet_ideal',
        'paycomet_klarna',
        'paycomet_giropay',
        'paycomet_mybank',
        'paycomet_multibanco',
        'paycomet_trustly',
        'paycomet_przelewy24',
        'paycomet_bancontact',
        'paycomet_eps',
        //'paycomet_tele2',
        'paycomet_paysera',
        'paycomet_postfinance',
        'paycomet_qiwi',
        //'paycomet_yandex',
        //'paycomet_mts',
        //'paycomet_beeline',
        'paycomet_paysafecard',
        'paycomet_skrill',
        //'paycomet_webmoney',
        'paycomet_instantcredit',
        'paycomet_klarnapayments',
        'paycomet_paypal',
        'paycomet_mbway',
        'paycomet_waylet'
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    private $methods = [];

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * PaycometConfigProvider constructor.
     *
     * @param PaymentHelper                     $paymentHelper
     * @param \Paycomet\Payment\Helper\Data     $helper
     * @param \Magento\Framework\Escaper        $escaper
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

        // Por cada metodo definimos las propiedades
        foreach ($this->_methodCodes as $code) {
            $config['payment'][$code] = []; // Inicializamos el array

            // Si está habilitado el método de pago
            if ($this->methods[$code]->isAvailable()) {
                switch ($code) {
                    case 'paycomet_payment':
                        $config['payment'] [$code]['redirectUrl'] = $this->getMethodRedirectUrl($code);
                        $config['payment'] [$code]['iframeEnabled'] = $this->_helper->getConfigData('iframe_enabled');
                        $config['payment'] [$code]['iframeMode'] = $this->_helper->getConfigData('iframe_mode');
                        $config['payment'] [$code]['iframeHeight'] = $this->_helper->getConfigData('iframe_height');
                        $config['payment'] [$code]['card_offer_save'] = (
                            $this->_helper->getConfigData('card_offer_save') &&
                            $this->_helper->getCustomerId()
                        );
                        $config['payment'] [$code]['paycometCards'] = $this->getPaycometToken($code);
                        $config['payment'] [$code]['form_footer'] = nl2br(
                            $this->_escaper->escapeHtml($this->_helper->getConfigData('form_footer'))
                        );
                        $config['payment'] [$code]['integration'] = $this->_helper->getConfigData('integration');
                        $config['payment'] [$code]['jetid'] = $this->_helper->getEncryptedConfigData('jetid');
                        $config['payment'] [$code]['isActive'] = $this->_helper->getConfigData('active', null, $code);
                        $config['payment'] [$code]['show_amex_img'] = $this->_helper->getConfigData('show_amex_img');
                        break;

                    // 'Apms'
                    default:
                        $config['payment'] [$code]['redirectUrl'] = $this->getMethodRedirectUrl($code);
                        $config['payment'] [$code]['isActive'] = $this->_helper->getConfigData('active', null, $code);
                        break;
                }
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
