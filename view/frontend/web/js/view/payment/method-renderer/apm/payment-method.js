/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Paycomet_Payment/js/action/set-payment-method',        
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',        
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'mage/url'
    ],
    function(ko, $, Component, setPaymentMethodAction, quote,
        additionalValidators, fullScreenLoader, errorProcessor, customer, $t,url) {
        'use strict';
        var paymentMethod = ko.observable(null);                

        return Component.extend({
            self: this,
            defaults: {
                template: 'Paycomet_Payment/payment/apm/paycomet-form'
            },            

            initialize: function() {
                this._super();                
            },
            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {}
                };

                return data;
            },            

            /** Redirect */
            continueToPayment: function(){

                if (this.validate() && additionalValidators.validate()){

                    setPaymentMethodAction() // Place Order
                        .done(
                            function(response){
                                $.mage.redirect(window.checkoutConfig.payment["paycomet_payment"].redirectUrl);
                            }
                        ).fail(
                            function(response){
                                errorProcessor.process(response);
                                fullScreenLoader.stopLoader();
                            }
                        );
                    return false;
                }
                $('button.checkout').prop( "disabled", false);
            },
            validate: function() {
                return true;
            },            
            
        });
    },

);
