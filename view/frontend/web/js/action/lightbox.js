define(
    [
        'jquery',
        'Paycomet_Payment/js/action/restore-cart',
        'Paycomet_Payment/js/model/paycomet-payment-service',
        'Magento_Ui/js/modal/modal',
        'mage/translate'
    ],
    function($, restoreCartAction, paycometPaymentService, modal, $t) {
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
                    paycometPaymentService.isInAction(false);
                    paycometPaymentService.isLightboxReady(false);
                }
            };

            $("#paycomet-iframe-container").modal(options).modal('openModal');
        };
    }
);
