(function ($, window) {
    'use strict';
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {

            _create: function () {
                this._super();
            },
            removeWait : function(elID){
                this._removeWait(elID);
            },

            _removeWait: function (elID) {
                //overlayBlock
                if ($("#osc-loader").length) {
                    $("#osc-loader").remove();
                }

            },
            runloadWait : function(){
                this._runLoadWait();
            },

            _runLoadWait: function () {
                if (this._itemsLoading.length == 0)
                    return false;
                //var _this = this;
                //var isOverlay = false;
                var loaderTemplateScript = $("#loader-template").html();  

                var loaderTemplate = Handlebars.compile(loaderTemplateScript);
                var isPopup = false;
                var popupId = '';

                for (var elID in this._itemsLoading) {
                    if (elID == 'loginFrm' || elID == 'forgotFrm') {
                        isPopup = true;
                        popupId = elID;
                    }
                    delete this._itemsLoading[elID];
                }
                if (isPopup) {
                    $('#' + popupId).append(loaderTemplate(loaderJson));
                } else {
                    if (!$("#osc-loader").length) {
                        $('body').append(loaderTemplate(loaderJson));
                    }

                }

                return false;

            },
            _itemsLoading: {},
            _loadWait: function (elID, isOverlay) {
                if (!isOverlay) isOverlay = false;
                this._itemsLoading[elID] = isOverlay;
            }
            ,

            reloadPartial : function(partials){
                var _this = this;
                if(!partials)
                    partials = 'all';
                var data = {
                    step : 'reload_partial',
                    'partial' : partials,
                }

                this.queueProcess('reload_partial',
                    {
                        url: '',
                        type: 'POST',
                        data: data,
                        async : false,
                        beforeSend: function () {

                        },
                        complete: function (response) {

                            var responseObject = $.parseJSON(response.responseText);
                            _this._updateHtml(responseObject);
                            _this._removeWait();
                        }
                    });
            }
        }
    )
})(jQuery, window);