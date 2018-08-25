(function ($, window) {
    'use strict';
    /* Errors */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {
        _create: function () {
            this._super();
        },
        hideErrors: function(){
            $(".error").parent('.error-container').addClass('hide');
            $(".error").html('');
            $("p.mage-error").html('');
            $("span.mage-error").html('');
            $("div.mage-error").html('');
        },
        showError: function(errorId, $message){
            $("#"+errorId).parent('.error-container').removeClass('hide');
            $("#"+errorId).html($message);
        },
    });

})(jQuery,window);
