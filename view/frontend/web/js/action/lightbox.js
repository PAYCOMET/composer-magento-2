define(
    [
        'jquery',
        'Paytpv_Payment/js/action/restore-cart',
        'Paytpv_Payment/js/model/paytpv-payment-service',
        'Magento_Ui/js/modal/modal',
        'mage/translate'
    ],
    function($, restoreCartAction, paytpvPaymentService, modal, $t) {
        'use strict';

        return function() {

            var options = {
                responsive: true,
                innerScroll: false,
                buttons: [{
                    class: '',
                    text: $t('Cancel'),
                    click: function() {
                        this.closeModal();
                    }
                }],
                closed: function() {
                    this.remove();
                    restoreCartAction();
                    paytpvPaymentService.isInAction(false);
                    paytpvPaymentService.isLightboxReady(false);
                }
            };

            $("#paytpv-iframe-container").modal(options).modal('openModal');
        };
    }
);
