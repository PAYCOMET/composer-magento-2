var config = {
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Paycomet_Payment/js/apm/instantcredit/price-box-mixin': true
            },
            'Magento_Checkout/js/model/cart/totals-processor/default': {
                'Paycomet_Payment/js/model/cart/totals-processor/apm/instantcredit/default-mixin': true
            },
        }
    }
};