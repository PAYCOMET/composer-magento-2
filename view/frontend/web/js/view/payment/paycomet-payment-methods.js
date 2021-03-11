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

        var config = window.checkoutConfig.payment,          
            paycomet_payment    = 'paycomet_payment',
            paycomet_paypal     = 'paycomet_paypal',
            paycomet_bizum      = 'paycomet_bizum';

        
        rendererList.push(
            {
                type: paycomet_payment,
                component: 'Paycomet_Payment/js/view/payment/method-renderer/payment-method'
            }
        );

        rendererList.push(
            {
                type: paycomet_paypal,
                component: 'Paycomet_Payment/js/view/payment/method-renderer/paypal/payment-method'
            }
        );

        rendererList.push(
            {
                type: paycomet_bizum,
                component: 'Paycomet_Payment/js/view/payment/method-renderer/bizum/payment-method'
            }
        );

        
            

        /** Add view logic here if needed */
        return Component.extend({
           
            getCustomerCards: function () {
                return window.checkoutConfig.payment[quote.paymentMethod().method].paycometCards;
            }


        });
    }
);
