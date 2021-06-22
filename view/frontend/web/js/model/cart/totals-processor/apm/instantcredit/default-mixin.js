define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'Paycomet_Payment/js/view/payment/apm/instantcredit/helper',
], function (wrapper, quote, $, icredit) {
    'use strict';

    return function (defaultTotalsProcessor) {
        defaultTotalsProcessor.estimateTotals = wrapper.wrapSuper(defaultTotalsProcessor.estimateTotals, function (address) {
            let classSimulator = '.ic-simulator';
            $(classSimulator).closest('.cart-ic-container').css('display','none');
            if (typeof icSimulator !== 'undefined' && $(classSimulator).length > 0) {
                // Remove odd simulator
                $(classSimulator).empty();
                let amountValue = quote.totals().grand_total;
                if (icredit.isBetweenLimits(parseFloat(amountValue))) {
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

        return defaultTotalsProcessor;
    };
});