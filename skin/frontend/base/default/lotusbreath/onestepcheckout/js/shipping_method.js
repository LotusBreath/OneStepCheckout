(function ($, window) {
    'use strict';
    /* Payment */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {
        options: {
            shippingMethod: {}
        },
        _create: function () {
            this._super();
            var _this = this;

            this.element
                .on('click', 'input[name="shipping_method"]:visible', function (e) {
                    var params = $("#checkout_form").serializeArray();
                    params[params.length] = {'name': 'step', 'value': 'shipping_method'};
                    params[params.length] = {'name': 'shipping_method', 'value': $(this).val()};
                    window.oscObserver.beforeUpdateShippingMethod();

                    _this._loadWait('review_block');
                    if (_this.options.checkoutProcess.shipping_method.loading_payment)
                        _this._loadWait('payment_block');

                    _this.executeProcess('delivery');
                    
                })
                .on('contentUpdated', $.proxy(function () {
                    this.currentShippingMethod = this.element.find('input[name="shipping_method"]:checked').val();
                    this.shippingCodePrice = this.element.find('[data-shipping-code-price]').data('shipping-code-price');
                }, this))
                .find(this.options.shippingMethod.form).validation();
        },

        /**
         * Make sure at least one shipping method is selected
         * @return {Boolean}
         * @private
         */
        _validateShippingMethod: function () {
            var methods = this.element.find('[name="shipping_method"]');
            $("#shippingmethod-error").html('');
            if (methods.length === 0) {
                this._showError("#shippingmethod-error", $.mage.__('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.'));
                //$("#shippingmethod-error").html();
                return true;
            }
            if (methods.filter(':checked').length) {
                return true;
            }
            this._showError("#shippingmethod-error", $.mage.__('Please specify shipping method.'));

            //$("#shippingmethod-error").html($.mage.__('Please specify shipping method.'));
            return false;
        },
        selectShippingMethod : function(shipping_method){
            var _this = this;
            var methods = jQuery.find('[name="shipping_method"]');

            methods.each(
                function(element){
                    if(jQuery(element).val() == shipping_method){
                        jQuery(element).prop("checked", true);
                        //console.log($(element));
                        jQuery(element).attr('checked', 'checked');
                    }
                }
            );

            var params = $("#checkout_form").serializeArray();
            //params[params.length] = {'name': 'step', 'value': 'shipping_method'};
            //params[params.length] = {'name': 'shipping_method', 'value': shipping_method};
            window.oscObserver.beforeUpdateShippingMethod();
            $("#shippingmethod-error").html("");

            if (_this.options.checkoutProcess.shipping_method.loading_payment)
                _this._loadWait('payment_block');

            _this.executeProcess('delivery');
            //alert(1);
            /*
            $.ajax({
                url: _this.options.saveStepUrl,
                type: 'POST',
                //async : false,
                data: params,
                beforeSend: function () {


                    _this._loadWait('review_block');

                },
                complete: function (response) {
                    try {
                        var responseObject = $.parseJSON(response.responseText);
                        window.oscObserver.afterUpdateShippingMethod(responseObject);
                    } catch (ex) {
                        _this._removeWait();
                        return false;
                    }
                    //responseObject['update_items'].each(function(elename){
                    //  console.log(elename)
                    //  $("#"+elename).html(responseObject['htmlUpdates'][elename]);
                    //})
                    //var elename = responseObject['update_items'][0];
                    //var elename = responseObject['update_items'][0];
                    //$("#reviewcart").html(responseObject['htmlUpdates'][elename]);

                    _this._updateHtml(responseObject);
                }

            });
            */
        }
    });

})(jQuery, window);