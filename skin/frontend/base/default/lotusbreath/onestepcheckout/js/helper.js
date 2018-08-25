(function ($, window) {
    'use strict';
    /* Others */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {

            _create: function () {
                this._super();
                /**
                 * Term and condition popup
                 */
                var that = this;
                $(".a-agreement").each(function () {
                    $(this).magnificPopup(
                        {
                            items: {
                                type: 'inline',
                                src: "#" + $(this).attr('rel'),
                                prependTo: that.options.checkout.checkoutForm
                            },
                            fixedContentPos: true
                            //modal: true
                        }
                    );
                });
            },
            _showError: function (errIdSel, message) {
                if (errIdSel) {
                    try {
                        if (errIdSel && message) {
                            $(errIdSel).html(message);
                            $(errIdSel).parent('.error-container').removeClass('hide');
                            //$("#"+errorId).html($message);
                            var ext_scrolled = 20;
                            if ($("#mj-topbar").length) {
                                ext_scrolled = $("#mj-topbar").outerHeight();
                            }
                            var scrollPos = $(errIdSel).offset().top - $(errIdSel).outerHeight() - ext_scrolled;
                            $('html,body').animate({scrollTop: scrollPos}, 500);
                        }

                    } catch (e) {
                    }
                }

            },
            autoDetectLocation : function(){
                $.getJSON(_this.options.autoDetectUrl, function (result) {

                    if (result.country_code) {
                        $(_this.options.billing.countryDropdownSelector).val(result.country_code);

                    }
                    if (result.region_code) {
                        $(_this.options.billing.region_id).val(result.region_code);
                    }
                    if (result.region_name) {
                        $(_this.options.billing.region).val(result.region_name);
                    }
                    if (result.city) {
                        $(_this.options.billing.city).val(result.city);
                    }
                    if (result.zip) {
                        //$(_this.options.billing.).val(result.city);
                    }
                    if (result.country_code || result.region_code || result.region_name || result.city) {
                        $(_this.options.billing.countryDropdownSelector) && $(_this.options.billing.countryDropdownSelector).trigger('change');
                    }
                    //shipping
                    if (result.country_code) {
                        $(_this.options.shipping.countryDropdownSelector).val(result.country_code);

                    }
                    if (result.region_code) {
                        $(_this.options.shipping.region_id).val(result.region_code);
                    }
                    if (result.region_name) {
                        $(_this.options.shipping.region).val(result.region_name);
                    }
                    if (result.city) {
                        $(_this.options.shipping.city).val(result.city);
                    }
                    if (result.country_code || result.region_code || result.region_name || result.city) {
                        if (!$(_this.options.billing.useForShippingAddressCheckboxId).is(':checked')
                            && !_this.options.billing.alwaysUseShippingAsBilling
                        ) {
                            $(_this.options.shipping.countryDropdownSelector) && $(_this.options.shipping.countryDropdownSelector).trigger('change');
                        }
                    }

                });
            }
        }
    )
})(jQuery, window);