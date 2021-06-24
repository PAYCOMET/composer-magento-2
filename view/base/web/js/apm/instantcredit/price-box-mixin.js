define([
    'jquery',
    'Paycomet_Payment/js/view/payment/apm/instantcredit/helper',
], function ($, paycomet_instantcredit) {
    'use strict';

    var priceBoxMixin = {

        /**
         * Render price unit block.
         */
        reloadPrice: function reDrawPrices() {
            this._super();

            let classSimulator = '.ic-simulator';
            if (typeof icSimulator !== 'undefined' && $(classSimulator).length > 0) {
                // Remove odd simulator
                $(classSimulator).empty();
                let amountValue = this.cache.displayPrices.finalPrice.amount;
                if (paycomet_instantcredit.isBetweenLimits(parseFloat(amountValue))) {
                    let valSimulator = amountValue.toString().replace('.', ',');
                    if (parseFloat(amountValue) === parseInt(amountValue)) {
                        valSimulator = parseFloat(amountValue) + ',00';
                    }
                    $(classSimulator).attr('amount', valSimulator);
                    // Refresh simulator
                    icSimulator.initialize();
                }
            }
        },
    };

    return function (targetWidget) {
        $.widget('mage.priceBox', targetWidget, priceBoxMixin);

        return $.mage.priceBox;
    };
});