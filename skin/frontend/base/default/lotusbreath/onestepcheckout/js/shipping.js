(function ($, window) {
    'use strict';
    /* Shipping */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {
        options: {
            shipping: {
                form: '#co-shipping-form',
                addressDropdownSelector: '#shipping-address-select',
                newAddressFormSelector: '#shipping-new-address-form',
                copyBillingSelector: '#shipping\\:same_as_billing',
                countrySelector: '#shipping\\:country_id',
                city: '#shipping\\:city',
                region: '#shipping\\:region',
                region_id: '#shipping\\:region_id',
                countryDropdownSelector: '#shipping\\:country_id',
                continueSelector: '#shipping-buttons-container .button',
                reloadImmediately : false
            }
        },
        _submitShippingTimeOut: false,

        _submitShippingChange: function () {
            var _this = this;

            var timeoutShippingFunction = function () {
                var reloadImmediately = _this.options.shipping.reloadImmediately;
                if (!reloadImmediately && $(_this.options.shipping.newAddressFormSelector + " input:focus ," + _this.options.shipping.newAddressFormSelector + " select:focus").length) {
                    _this._submitShippingTimeOut = setTimeout(timeoutShippingFunction, 500);
                    return;
                }
                _this._updateLocation(null);
                if(_this._submitShippingTimeOut)
                    clearTimeout(_this._submitShippingTimeOut);
            };

            if(_this.options.shipping.reloadImmediately){
                timeoutShippingFunction();
            }else{
                this._submitBillingTimeOut = setTimeout(timeoutShippingFunction, 100);
            }

            _this._submitShippingTimeOut = setTimeout(timeoutShippingFunction, 500);

        },
        _onchangeShippingLocactionFields: function () {
            var _this = this;

            $(_this.options.shipping.newAddressFormSelector + " input ," + _this.options.billing.newAddressFormSelector + " select")
                .on('change', function () {
                    if (!$(this).hasClass('change_location_field') && $(this).hasClass('required-entry')) {
                        var reloadImmediately = _this.options.shipping.reloadImmediately;
                        var needUpdated = true;
                        if(!reloadImmediately){
                            $(_this.options.shipping.newAddressFormSelector + " input ," + _this.options.billing.newAddressFormSelector + " select").each(
                                function (i, ele) {
                                    //console.log( $(ele).name + ' : ' + $(ele).val());
                                    if (!$(ele).val() && $(ele).hasClass('required-entry') && $(ele).is(':visible'))
                                        needUpdated = false;
                                }
                            );
                        }

                        if (needUpdated) {
                            _this._submitShippingChange();
                        }
                    }
                }
            );

            $(_this.options.shipping.newAddressFormSelector + " .change_location_field").on('change', function () {

                _this._submitShippingChange();
            });

        },
        _create: function () {
            this._super();
            var _this = this;
            this._onchangeShippingLocactionFields();
            this.element
                .on('change', _this.options.shipping.addressDropdownSelector, $.proxy(function (e) {
                    $(this.options.shipping.newAddressFormSelector).toggle(!$(e.target).val());
                    var data = {
                        'country_id': $(_this.options.shipping.countrySelector).val()
                    };

                    _this._updateLocation(data);
                }, this))
                .on('click', _this.options.billing.useForShippingAddressCheckboxId, function (e) {
                    var data = {
                        'country_id': $(_this.options.billing.countryDropdownSelector).val()
                    };

                    _this._updateLocation(data);
                })
                .on('input propertychange', this.options.shipping.form + ' :input[name]', $.proxy(function () {
                    $(this.options.shipping.copyBillingSelector).prop('checked', false);
                }, this))
                .on('click', this.options.shipping.copyBillingSelector, $.proxy(function (e) {

                    if ($(e.target).is(':checked')) {
                        this._billingToShipping();
                    }
                }, this))
                .find(this.options.shipping.form).validation();
            return this;
        },

        /**
         * Copy billing address info to shipping address
         * @private
         */
        _billingToShipping: function () {
            $(':input[name]', this.options.billing.form).each($.proxy(function (key, value) {
                var fieldObj = $(value.id.replace('billing:', '#shipping\\:'));
                fieldObj.val($(value).val());
                if (fieldObj.is("select")) {
                    fieldObj.trigger('change');
                }
            }, this));
            $(this.options.shipping.copyBillingSelector).prop('checked', true);
        }
    });
})(jQuery);
