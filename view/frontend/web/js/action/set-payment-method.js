define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate'

    ],
    function($, quote, urlBuilder, storage, customer, fullScreenLoader,$t) {
        'use strict';
        var agreementsConfig = window.checkoutConfig.checkoutAgreements;


        return function() {

            var serviceUrl,
                payload,
                paymentData = quote.paymentMethod();
            if (paymentData.title) {
                delete paymentData.title;
            }
            
            // PAYTPV additional_data
            paymentData.additional_data = {
                saveCard: $("#paytpv_savecard").is(':checked')?1:0,
                paytpvCard: $("#paytpv_card").val()
            }


            // check if agreement is enabled if so add it to payload
            if (agreementsConfig.isEnabled) {
                var agreementForm = $('.payment-method._active form[data-role=checkout-agreements]'),
                    agreementData = agreementForm.serializeArray(),
                    agreementIds = [];

                agreementData.forEach(function(item) {
                    agreementIds.push(item.value);
                });

                paymentData.extension_attributes = {
                    agreement_ids: agreementIds
                };
            }

            /** Checkout for guest and registered customer. */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            }
            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl, JSON.stringify(payload)
            );
        };
    }
);
