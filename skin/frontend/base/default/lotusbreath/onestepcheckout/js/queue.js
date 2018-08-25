(function ($, window) {
    'use strict';
    /* Payment */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {

            _create: function () {
                this._super();
                var _this = this;
                $(document).bind("ajaxSend", function (e, xhr, ajaxOptions) {
                    var parametters = $.parseParams(ajaxOptions.data);
                    if (_this.options.autoDetectUrl == ajaxOptions.url)
                        return false;

                    var loading = $.getUrlQueryParam(ajaxOptions.url,'loading');

                    if(loading != 'undefined' && loading == 0){
                        return false;
                    }

                    if (parametters.step) {
                        var stop = false;
                        $("#checkout_form").validation();
                        var validator = $("#checkout_form").validate();
                        var isCheckedBilling = false;
                        var isCheckedShipping = false;
                        if (
                            parametters.step == 'payment_method' ||
                            (parametters.step == 'shipping_method' && $("#billing\\:use_for_shipping_yes").is(":checked") )) {
                            isCheckedBilling = false;

                        } else {
                            if (parametters.step == 'shipping_method') {
                                isCheckedShipping = true;
                            }
                        }
                        if (isCheckedBilling) {
                            $("#billing-new-address-form .change_location_field.required-entry").each(function () {
                                if ($(this).is(":visible")) {
                                    if (!( $(this).valid())) {
                                        stop = true;
                                        validator.focusInvalid();
                                    }
                                }
                            });
                        }
                        if (isCheckedShipping) {
                            $("#shipping-new-address-form .change_location_field.required-entry").each(function () {
                                if ($(this).is(":visible")) {
                                    if (!( $(this).valid())) {
                                        stop = true;
                                        validator.focusInvalid();
                                    }
                                }
                            });
                        }
                        if (stop) {
                            xhr.abort();
                            _this._removeWait();
                            return false;
                        }
                    }

                    _this.hideErrors();
                    _this._runLoadWait();
                });

                $(document).bind("ajaxStop", function (e, xhr, ajaxOptions) {
                    _this._removeWait();
                });
                $(document).bind("ajaxError", function (e, xhr, ajaxOptions) {
                    if(xhr.status == 403){

                    }
                });

                $(document).bind("ajaxcomplete", function (e, xhr, ajaxOptions) {

                    _this._removeWait();
                });
            },
            queueProcess: function (name, ajaxOptions) {
                if($.ajaxq.isRunning(name))
                    return;
                $.ajaxq (name, ajaxOptions);
            }
        }
    )
})(jQuery, window);