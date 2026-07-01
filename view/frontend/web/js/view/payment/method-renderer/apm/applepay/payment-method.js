/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Paycomet_Payment/js/action/set-payment-method',
        'Paycomet_Payment/js/action/restore-cart',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'mage/url',
        'Magento_Ui/js/model/messageList'
    ],
    function(ko, $, Component, setPaymentMethodAction, restoreCartAction, quote,
        additionalValidators, fullScreenLoader, errorProcessor, customer, $t, url, globalMessageList) {
        'use strict';

        return Component.extend({


            defaults: {
                template: 'Paycomet_Payment/payment/apm/applepay/paycomet-form'
            },

            isApplePayVisible: ko.observable(false),

            applePayButtonStyle: ko.observable(window.checkoutConfig.payment["paycomet_applepay"].color),
            applePayButtonWidth: ko.observable(window.checkoutConfig.payment["paycomet_applepay"].width),
            applePayButtonHeight: ko.observable(window.checkoutConfig.payment["paycomet_applepay"].height),
            applePayButtonLocale: ko.observable(document.documentElement.lang),

            initialize: function() {
                this._super();
                var self = this;

                jQuery.getScript("https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js");
                self.isApplePayVisible(true);
                fullScreenLoader.stopLoader();

                return this;
            },


            isActive: function () {
                var active = this.getCode() === this.isChecked();

                this.active(active);

                return active;
            },


            validate: function() {
                return true;
            },


            processApplePay: function (data, event) {
                var self = this;
                if (event) { event.preventDefault(); }

                if (typeof ApplePaySession === 'undefined' || !ApplePaySession) {
                    console.error("Apple Pay no está disponible en este navegador.");
                    return;
                }

                var totals = quote.totals();
                var grandTotal = totals ? parseFloat(totals.base_grand_total).toFixed(2) : "0.00";
                var currency = totals ? totals.base_currency_code : "EUR";

                var currentApplePayConfig;

                // Generamos el request para iniciar sesion en ApplePay
                const request = {
                    "countryCode": "ES",
                    "currencyCode": currency,
                    "merchantCapabilities": ["supports3DS"],
                    "supportedNetworks": ["visa", "masterCard", "discover", "amex"],
                    "total": {
                        "label": window.checkoutConfig.payment["paycomet_applepay"].storeName,
                        "type": "final",
                        "amount": grandTotal
                    }
                };

                const session = new ApplePaySession(3, request);

                session.begin();

                session.onvalidatemerchant = async merchantEvent => {
                    try {
                        await setPaymentMethodAction(); // Registramos la intención en Magento

                        const configResponse = await $.ajax({
                            url: url.build('paycomet_payment/applepay/info'),
                            type: 'GET',
                            dataType: 'json'
                        });

                        if (configResponse && configResponse.success && configResponse.data.applePayConfig) {

                            self.currentApplePayConfig = configResponse.data.applePayConfig;

                            const validationUrl = "https://" + self.currentApplePayConfig.domain + "/gateway/applepay_check.php?ss=" + self.currentApplePayConfig.sessionToken;
                            const appleResponse = await fetch(validationUrl);
                            const merchantSession = await appleResponse.json();

                            session.completeMerchantValidation(merchantSession);
                        } else {
                            console.log($t('An unexpected error occurred when initializing Apple Pay'))
                            throw new Error($t('An unexpected error occurred when initializing Apple Pay'));
                        }
                    } catch (error) {
                        fullScreenLoader.stopLoader();
                        session.abort();
                        console.log($t('An unexpected error occurred when initializing Apple Pay'))
                        globalMessageList.addErrorMessage({
                            message: $t('An unexpected error occurred when initializing Apple Pay')
                        });

                    }
                };

                session.onpaymentmethodselected = event => {
                    const update = {
                        "newTotal": request.total || {}
                    };
                    session.completePaymentMethodSelection(update);
                };


                session.onshippingmethodselected = event => {
                    const update = {
                        "total": request.total || {}
                    };
                    session.completeShippingMethodSelection(update);
                };

                session.onshippingcontactselected = event => {
                    const update = {
                        "total": request.total || {}
                    };
                    session.completeShippingContactSelection(update);
                };

                session.onpaymentauthorized = event => {
                    if (!event.payment.token.paymentData) {
                        session.completePayment({"status": ApplePaySession.STATUS_FAILURE});
                        return;
                    }

                    $.ajax({
                        method: "POST",
                        url: "https://api.paycomet.com/gateway/xpay.php",
                        data: {
                            payload: event.payment.token.paymentData,
                            origin: 'ApplePay',
                            accountId: self.currentApplePayConfig.accountId || 0,
                            productId: self.currentApplePayConfig.productId || 0,
                            action: 'pay',
                            session: self.currentApplePayConfig.session
                        }
                    }).done(function (result) {

                        if (!result.data || result.error) {
                            restoreCartAction()
                            session.completePayment({"status": ApplePaySession.STATUS_FAILURE});
                            fullScreenLoader.stopLoader();
                            return;
                        }

                        if (result.data.url) {
                            session.completePayment({"status": ApplePaySession.STATUS_SUCCESS});
                            return window.location.href = result.data.url;
                        }
                    })
                };

                session.oncancel = event => {
                    restoreCartAction();
                    fullScreenLoader.stopLoader();
                    if (typeof untrapFocus !== 'undefined') {
                        untrapFocus();
                    }
                };

                if (typeof trapFocus !== 'undefined') {
                    trapFocus();
                }
            },




        });
    },

);
