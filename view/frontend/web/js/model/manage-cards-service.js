define(
    [
        'ko',
        'jquery',
        'mage/translate'
    ],
    function(ko, $, $t) {
        'use strict';
        var iframeHeight = ko.observable('640px');
        var iframeWidth = ko.observable('100%');
        
        var iframeUrl = ko.observable($('#paycomet-iframe').val());
        var jetId = ko.observable($('#paycomet-jetid').val());
        var displayMessage =  ko.observable(false);

       
        
        return {
            iframeHeight: iframeHeight,
            iframeWidth: iframeWidth,
            iframeUrl: iframeUrl,
            jetId: jetId,
            
            displayMessage: displayMessage,

            iframeResize: function(event) {
                try {
                    var data = JSON.parse(event);
                    if (data.iframe) {
                        if (this.iframeHeight() != data.iframe.height) {
                            this.iframeHeight(data.iframe.height);
                        }
                        if (this.iframeWidth() != data.iframe.width) {
                            this.iframeWidth(data.iframe.width);
                        }
                    }
                } catch (e) {
                    return false;
                }
            }
        };
    });
