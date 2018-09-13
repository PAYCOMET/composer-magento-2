/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Paytpv_Payment/js/action/set-payment-method',
        'Paytpv_Payment/js/action/lightbox',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Paytpv_Payment/js/model/paytpv-payment-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'mage/translate',
        'mage/url'

    ],
    function(ko, $, Component, setPaymentMethodAction, lightboxAction, quote,
        additionalValidators, paytpvPaymentService, fullScreenLoader, errorProcessor, customer, $t,url) {
        'use strict';
        var paymentMethod = ko.observable(null);
        var paytpv_save_card = ko.observable(false);
        var isOfferSave = ko.observable(false);
        var isVisibleCards = ko.observable(customer.isLoggedIn());
        var place = false;

        $("body").on('click','#paytpv_open_conditions',function(){
                        
            var options = {
                
                responsive: true,
                innerScroll: false,
                buttons: [{
                    class: '',
                    text: $t('Close'),
                    click: function() {
                        this.closeModal();
                    }
                }]
            };
            $("#paytpv-conditions").modal(options).modal('openModal');


        });



        return Component.extend({
            self: this,
            defaults: {
                template: 'Paytpv_Payment/payment/paytpv-form'
            },
            isInAction: paytpvPaymentService.isInAction,
            isLightboxReady: paytpvPaymentService.isLightboxReady,
            iframeHeight: paytpvPaymentService.iframeHeight,
            iframeWidth: paytpvPaymentService.iframeWidth,
            
            paytpv_save_card: paytpv_save_card,

            isOfferSave: isOfferSave,
            isVisibleCards: isVisibleCards,

            initialize: function() {
                this._super();
                $(window).bind('message', function(event) {
                    paytpvPaymentService.iframeResize(event.originalEvent.data);
                });
            },
            resetIframe: function() {
                this.isLightboxReady(false);
                this.isInAction(false);
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'saveCard': $("#paytpv_savecard").is(':checked')?1:0,
                        'paytpvCard': $("#paytpv_card").val()
                    }
                };

                return data;
            },

            /**
             * Get action url for payment method iframe.
             * @returns {String}
             */
            getActionUrl: function() {
                return this.isInAction() ? window.checkoutConfig.payment["paytpv_payment"].redirectUrl : '';
            },

            /**
             * Get action url for payment method iframe.
             * @returns {String}
             */
            getFormFooter: function() {
                return window.checkoutConfig.payment["paytpv_payment"].form_footer;
            },

            /** Redirect */
            continueToPayment: function(){
                
                this.resetIframe();

                if (this.validate() && additionalValidators.validate()){
                    
                    setPaymentMethodAction() // Place Order
                        .done(
                            function(response){
                                if (window.checkoutConfig.payment["paytpv_payment"].iframeEnabled === '1' && $("#paytpv_card").val()=="") {
                                    paytpvPaymentService.isInAction(true);
                                    paytpvPaymentService.isLightboxReady(true);
                                    if (window.checkoutConfig.payment["paytpv_payment"].iframeMode === 'lightbox') {
                                        lightboxAction();
                                    }else {
                                        // capture all click events
                                        document.addEventListener('click', paytpvPaymentService.leaveIframeForLinks, true);
                                    }
                                }else{

                                    $.mage.redirect(window.checkoutConfig.payment["paytpv_payment"].redirectUrl);
                                }
                                
                            }
                        ).fail(
                            function(response){
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
             * Load User PAYTPV cards
             */
            getSelectorPaytpvCards: function () {

                var Cards = window.checkoutConfig.payment["paytpv_payment"].paytpvCards;
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
 
            /**
             * Show / Hide Save card check
             */
            showSaveCard: function(){
                if ($("#paytpv_card").val()!=""){
                    isOfferSave(false);
                    paytpv_save_card(false);
                }else{
                    isOfferSave(window.checkoutConfig.payment["paytpv_payment"].card_offer_save==1);
                    paytpv_save_card(window.checkoutConfig.payment["paytpv_payment"].remembercardselected==1);
                }
            }
            
            

            
        });
    }
);
