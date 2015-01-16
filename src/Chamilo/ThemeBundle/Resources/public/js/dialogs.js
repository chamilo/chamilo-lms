;(function($, window){
    "use strict";

    var doConfirm = function(event) {
        var $t = $(event.currentTarget) , msg = $t.attr('data-confirm'), href = $t.attr('href');
        event.preventDefault();
        bootbox.confirm(msg, function(result){
            if(result === true) {
                window.location = href;
            }
        });
    },
    onInit = function(){
        $('[data-confirm]').off('click').on('click', doConfirm);
    };

    onInit();
    $(window).on('ajax.reloaded', onInit);

})(jQuery, window);