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

        rendererList.push(
            {
                type: 'paytpv_payment',
                component: 'Paytpv_Payment/js/view/payment/method-renderer/payment-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({
           
            getCustomerCards: function () {
                return window.checkoutConfig.payment[quote.paymentMethod().method].paytpvCards;
            }


        });
    }
);
