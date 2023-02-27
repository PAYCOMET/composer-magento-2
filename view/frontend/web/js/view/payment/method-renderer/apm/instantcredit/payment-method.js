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
        'Paycomet_Payment/js/view/payment/apm/instantcredit/helper'
    ],
    function(ko, $, Component, setPaymentMethodAction, quote,
        additionalValidators, fullScreenLoader, errorProcessor, paycomet_instantcredit) {
        'use strict';
        var paymentMethod = ko.observable(null);

        // if totals update refresh simulator
        quote.totals.subscribe(function() {
            let classSimulator = '.ic-simulator';
            $(classSimulator).closest('.cart-ic-container').css('display','none');
            if (typeof icSimulator !== 'undefined' && $(classSimulator).length > 0) {
                // Remove odd simulator
                $(classSimulator).empty();
                let amountV = quote.totals().base_grand_total;
                let amountValue = parseFloat(amountV).toFixed(2);
                if (paycomet_instantcredit.isBetweenLimits(parseFloat(amountValue))) {
                    let valSimulator = amountValue.toString().replace('.', ',');
                    if (parseFloat(amountValue) === parseInt(amountValue)) {
                        valSimulator = parseFloat(amountValue) + ',00';
                    }
                    $(classSimulator).attr('amount', valSimulator);
                    // Refresh simulator
                    icSimulator.initialize();
                    // We have simulator always because price can change in cart (cause shipping modify price)
                    $(classSimulator).closest('.cart-ic-container').css('display','block');
                }
            }
        });

        return Component.extend({
            self: this,
            defaults: {
                template: 'Paycomet_Payment/payment/apm/instantcredit/paycomet-form'
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

            getTotalPrice: function()
            {
                let totals = quote.totals();

                return totals.base_grand_total;
            },

            getTotalPriceFormatted: function()
            {
                let amountValue = this.getTotalPrice();
                let valSimulator = amountValue.toString().replace('.', ',');
                if (parseFloat(amountValue) === parseInt(amountValue)) {
                    valSimulator = parseFloat(amountValue) + ',00';
                }

                return valSimulator;
            },

            initializeICSimulator: function()
            {
                if (typeof icSimulator !== 'undefined') {
                    icSimulator.initialize();
                }
            },


            placeOrder: function (data, event) {
                this.continueToPayment();
            },


            /** Redirect */
            continueToPayment: function(){

                if (this.validate() && additionalValidators.validate()){
                    var method = this.getCode();
                    setPaymentMethodAction() // Place Order
                        .done(
                            function(response){
                                $.mage.redirect(window.checkoutConfig.payment[method].redirectUrl);
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
