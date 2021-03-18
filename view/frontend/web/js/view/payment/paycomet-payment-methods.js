define(
    [
        'uiComponent',
        'ko',
        'Magento_Checkout/js/model/payment/renderer-list',
        'underscore',
        'mage/translate'
    ],
    function (Component,ko,rendererList,_,$t) {
        'use strict';

        var config = window.checkoutConfig.payment;        

        var paycomet_payment    = 'paycomet_payment';
        if (config[paycomet_payment].isActive) {
            rendererList.push(
                {
                    type: paycomet_payment,
                    component: 'Paycomet_Payment/js/view/payment/method-renderer/payment-method'
                }
            );
        }


        // APMs
        var arrAPM = [
            'paypal',
            'bizum',
            'ideal',
            'klarna',
            'giropay',
            'mybank',
            'multibanco',
            'trustly',
            'przelewy24',
            'bancontact',
            'eps',
            'tele2',
            'paysera',
            'postfinance',
            'qiwi',
            'yandex',
            'mts',
            'beeline',
            'skrill',
            'webmoney',
            'instantcredit'
        ];

        for (var i = 0; i < arrAPM.length; i+=1) {            
            var apm_type = 'paycomet_' + arrAPM[i];            
            //var apm_component = 'Paycomet_Payment/js/view/payment/method-renderer/apm/'+arrAPM[i]+'/payment-method';
            var apm_component = 'Paycomet_Payment/js/view/payment/method-renderer/apm/payment-method';
            if (config[apm_type].isActive) {
                rendererList.push(
                    {
                        type: apm_type,
                        component: apm_component
                    }
                );
            }
        }
        /** Add view logic here if needed */
        return Component.extend({

            getCustomerCards: function () {
                return window.checkoutConfig.payment[quote.paymentMethod().method].paycometCards;
            }

        });
    }
);
