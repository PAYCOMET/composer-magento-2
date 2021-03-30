define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'ko',
        'Paycomet_Payment/js/model/manage-cards-service',
        'Magento_Ui/js/modal/modal',      
        'mage/translate'
    ],
    function($, Component, ko, manageCards, modal, $t) {
        'use strict';

        var isVisibleIframe = ko.observable(false);
        var isVisibleJetIframe = ko.observable(false);
        

        $( "#paycometcards-view-form a").click(function() {
            
            var data = $.parseJSON($(this).attr("data-post"));

            var role = $(this).attr("data-role");
            if (role=="remove")
                if (!confirm($t('Are you sure?')))
                    return false;
           
            $("#paycometcards-view-form").action = data['action'];

            data['data']['card_desc'] = $("#card_desc_"+data['data']['item']).val();

            $("#paycometcards-view-form a").attr("data-post",JSON.stringify(data));
            $("#paycometcards-view-form").action = data['action'];


        });
        

        // Show loading
        $("body").on('click','#paycomet_showcarddata',function() {
            if ($(this).is(":checked")) {
                if (jQuery("#paycomet-integration").val()==0) {
                    isVisibleIframe(true);
                    jQuery('body').trigger('processStart');
                } else {
                    isVisibleJetIframe(true);                    
                }                                
            } else {
                isVisibleIframe(false);
                isVisibleJetIframe(false);                
            }
            //
        });
        

        return Component.extend({
            iframeHeight: manageCards.iframeHeight,
            iframeWidth: manageCards.iframeWidth,
            iframeUrl: manageCards.iframeUrl,
            jetId: manageCards.jetId,

            displayMessage: manageCards.displayMessage,

            isVisibleIframe: isVisibleIframe,
            isVisibleJetIframe: isVisibleJetIframe,
            
            defaults: {
                template: 'Paycomet_Payment/cards/manage'
            },
            initObservable: function() {
                return this;
            },
            initialize: function() {

                this._super();
                $(window).bind('message', function(event) {
                    manageCards.iframeResize(event.originalEvent.data);
                });
            },

            jetIframeLoad: function() {
                jQuery.getScript("https://api.paycomet.com/gateway/paycomet.jetiframe.js");
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
            },

        });
    }
);
