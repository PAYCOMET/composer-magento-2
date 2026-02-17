define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'Paycomet_Payment/js/view/payment/apm/instantcredit/helper'
], function (wrapper, quote, $, icredit) {
    'use strict';

    return function (defaultTotalsProcessor) {
        defaultTotalsProcessor.estimateTotals = wrapper.wrapSuper(
            defaultTotalsProcessor.estimateTotals,
            function (address) {
                // 1. Ejecutamos la lógica original de Magento primero (super)
                var result = this._super(address);

                // 2. Lógica de Paycomet (Instant Credit Simulator)
                $.when(result).always(function () {
                    var classSimulator = '.ic-simulator';
                    $(classSimulator).closest('.cart-ic-container').hide();

                    if (typeof icSimulator === 'undefined' || $(classSimulator).length === 0) {
                        return;
                    }

                    var totals = quote.totals && quote.totals();
                    if (!totals || totals.base_grand_total == null) {
                        return;
                    }

                    var amountValue = Number(totals.base_grand_total);
                    if (!Number.isFinite(amountValue)) {
                        return;
                    }

                    amountValue = Number(amountValue.toFixed(2));

                    if (!icredit.isBetweenLimits(amountValue)) {
                        return;
                    }

                    $(classSimulator).empty();
                    var valSimulator = amountValue.toFixed(2).replace('.', ',');
                    $(classSimulator).attr('amount', valSimulator);
                    icSimulator.initialize();
                    $(classSimulator).closest('.cart-ic-container').show();
                });

                // 3. Devolvemos el resultado original para mantener la cadena
                return result;
            }
        );

        return defaultTotalsProcessor;
    };
});