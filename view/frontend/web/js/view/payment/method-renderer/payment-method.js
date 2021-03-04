/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Paycomet_Payment/js/action/set-payment-method',
        'Paycomet_Payment/js/action/lightbox',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Paycomet_Payment/js/model/paycomet-payment-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'mage/url'
    ],
    function(ko, $, Component, setPaymentMethodAction, lightboxAction, quote,
        additionalValidators, paycometPaymentService, fullScreenLoader, errorProcessor, customer, $t,url) {
        'use strict';
        var paymentMethod = ko.observable(null);
        var isOfferSave = ko.observable(false);
        var isVisibleCards = ko.observable(customer.isLoggedIn());
        var isVisibleJetIframe = ko.observable(false);
        var isVisibleButton = ko.observable(false);
        var expiry = ko.observable("");
        var place = false;


        return Component.extend({
            self: this,
            defaults: {
                template: 'Paycomet_Payment/payment/paycomet-form'
            },
            isInAction: paycometPaymentService.isInAction,
            isLightboxReady: paycometPaymentService.isLightboxReady,
            iframeHeight: paycometPaymentService.iframeHeight,
            iframeWidth: paycometPaymentService.iframeWidth,

            isOfferSave: isOfferSave,
            isVisibleCards: isVisibleCards,
            isVisibleJetIframe: isVisibleJetIframe,
            isVisibleButton: isVisibleButton,
            expiry: expiry,

            initialize: function() {
                this._super();
                if (window.checkoutConfig.payment["paycomet_payment"].integration==0) {
                    $(window).bind('message', function(event) {
                        paycometPaymentService.iframeResize(event.originalEvent.data);
                    });
                }
                
                // if jetIframe
                if (window.checkoutConfig.payment["paycomet_payment"].integration==1) {
                    jQuery.getScript("https://api.paycomet.com/gateway/paycomet.jetiframe.js");
                }

            },
            resetIframe: function() {
                this.isLightboxReady(false);
                this.isInAction(false);
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'saveCard': $("#paycomet_savecard").is(':checked')?1:0,
                        'paycometCard': $("#paycomet_card").val(),
                        'paycometJetToken':  $("input[name='paytpvToken']" ).val()
                    }
                };

                return data;
            },

            /**
             * Get action url for payment method iframe.
             * @returns {String}
             */
            getActionUrl: function() {
                return this.isInAction() ? window.checkoutConfig.payment["paycomet_payment"].redirectUrl : '';
            },

            /**
             * Get action url for payment method iframe.
             * @returns {String}
             */
            getFormFooter: function() {
                return window.checkoutConfig.payment["paycomet_payment"].form_footer;
            },

            /**
             * Get jetId.
             * @returns {String}
             */
            getJetId: function() {
                return window.checkoutConfig.payment["paycomet_payment"].jetid;
            },


            /** Redirect */
            continueToPayment: function(){

                this.resetIframe();

                if (this.validate() && additionalValidators.validate()){

                    setPaymentMethodAction() // Place Order
                        .done(
                            function(response){
                                if (window.checkoutConfig.payment["paycomet_payment"].integration==0 &&
                                    window.checkoutConfig.payment["paycomet_payment"].iframeEnabled === '1' &&
                                    $("#paycomet_card").val()=="") {
                                    paycometPaymentService.isInAction(true);
                                    paycometPaymentService.isLightboxReady(true);
                                    if (window.checkoutConfig.payment["paycomet_payment"].iframeMode === 'lightbox') {
                                        lightboxAction();
                                    }else {
                                        // capture all click events
                                        document.addEventListener('click', paycometPaymentService.leaveIframeForLinks, true);
                                    }
                                }else{
                                    $.mage.redirect(window.checkoutConfig.payment["paycomet_payment"].redirectUrl);
                                }
                            }
                        ).fail(
                            function(response){
                                $('button.checkout').prop( "disabled", false);
                                errorProcessor.process(response);
                                fullScreenLoader.stopLoader();
                            }
                        );

                    return false;
                }
            },
            validate: function() {
                return true;
            },
            /**
             * Hide loader when iframe is fully loaded.
             * @returns {void}
             */
            iframeLoaded: function() {
                fullScreenLoader.stopLoader();
            },

            /**
             * Load User PAYCOMET cards
             */
            getSelectorPaycometCards: function () {

                var Cards = window.checkoutConfig.payment["paycomet_payment"].paycometCards;
                return _.union(
                    _.map(Cards, function (card) {
                        return {
                            'hash': card.hash,
                            'desc': card.cc + " [" + card.brand + "] " + ((card.desc)?card.desc:'')
                        };
                    }),
                    [{'hash': '', 'desc': $t('NEW CARD')}]
                );
            },

            buttonClick: function() {
                if ($("#paycomet_card").val()!="") {
                    this.continueToPayment();
                } else {
                    $("#paycometPaymentForm").submit();
                }
            },

            /**
             * Show / Hide Save card check
             */
            showSaveCard: function(){

                /* Ocultar select de tarjetas cuando no tiene ninguna almacenada*/
                if ( $("#paycomet_card > option").length==1) {
                    isVisibleCards(false);
                }
                /* Si hay una tarjeta tokenizada seleccionda */
                if ($("#paycomet_card").val()!="") {
                    isOfferSave(false);
                    isVisibleJetIframe(false);
                    isVisibleButton(true)
                } else {
                    isOfferSave(window.checkoutConfig.payment["paycomet_payment"].card_offer_save==1);
                    isVisibleJetIframe(window.checkoutConfig.payment["paycomet_payment"].integration==1);
                    // Se oculta el boton principal cuando no hay tarjeta seleccionada y es jetIframe
                    isVisibleButton(window.checkoutConfig.payment["paycomet_payment"].integration!=1);
                }
            },

            buildED: function(){
                var t = document.getElementById('expiry_date').value,
                    n = t.substr(0, 2),
                    a = t.substr(3, 2);
                $('[data-paycomet=\'dateMonth\']').val(n), $('[data-paycomet=\'dateYear\']').val(a);
            },

            expiryDate: function() {
                var curLength = $("#expiry_date").val().length;
                if(curLength === 2){
                    var newInput = $("#expiry_date").val();
                    newInput += '/';
                    $("#expiry_date").val(newInput);
                }
            }

        });
    },

);


function jetAction() {
    jQuery("#submit_jet").click();
}