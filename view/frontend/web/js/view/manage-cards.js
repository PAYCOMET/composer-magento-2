define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'ko',
        'Paycomet_Payment/js/model/manage-cards-service',
        'Magento_Ui/js/modal/modal',
        'mage/translate'
    ],
    function($, Component, ko, manageCards,modal,$t) {
        'use strict';


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


        $("body").on('click','#paycomet_open_conditions',function() {
                        
            var options = {
                
                responsive: true,
                innerScroll: false,
                buttons: [{
                    class: '',
                    text: $t('Cancel'),
                    click: function() {
                        this.closeModal();
                    }
                }]
            };
            $("#paycomet-conditions").modal(options).modal('openModal');


        });

        // Show loading
        $("body").on('click','#paycomet_showcarddata',function() {
            if ($(this).is(":checked"))
                jQuery('body').trigger('processStart');         
        });



        

        return Component.extend({
            iframeHeight: manageCards.iframeHeight,
            iframeWidth: manageCards.iframeWidth,
            iframeUrl: manageCards.iframeUrl,

            displayMessage: manageCards.displayMessage,
            
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
            }
        });
    }
);
