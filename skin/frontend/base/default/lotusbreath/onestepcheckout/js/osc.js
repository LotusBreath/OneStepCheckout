/**
 * lotusbreath
 * File osc.js
 */
(function ($, window) {
    'use strict';
    $.widget('lotusbreath.onestepcheckout', {
        options: {
            checkout: {
                loginFormSelector: '#login-form',
                continueSelector: '#lbonepage-place-order-btn',
                registerCustomerPasswordSelector: '#register-customer-password',
                suggestRegistration: false,
                checkoutForm: '#checkout_form'
            },
            currentPaymentMethod : false,
            currentShippingMethod : false
        },
        _previous_data: {}
        ,
        _create: function () {
            var _this = this;
            this._addInputCartQty();
            this.bindSubmitOrderEvent();

            var that = this;

            return this;

        },
        getOptions : function(){
            return this.options;
        },

        bindSubmitOrderEvent: function(){
            var _this = this;
            $(this.options.checkout.continueSelector).unbind('click.lotusOSC');
            $(this.options.checkout.continueSelector).bind('click.lotusOSC', function(e){
                e.preventDefault();
                $.proxy(_this._save($(this)), _this);
                $(".mage-error").show();
                return false;
            });
        },



        _updateHtml: function (responseObject) {
            var _this = this;
            if (responseObject.previous_data) {
                this._previous_data = responseObject.previous_data;
            }
            _this._removeWait();
            var updateItems = new Array();
            if (responseObject.htmlUpdates) {
                for (var idx in responseObject.htmlUpdates) {
                    if (responseObject.update_items.indexOf(idx) >= 0) {
                        $("#" + idx).html(responseObject.htmlUpdates[idx]);

                        if (idx == 'review_block') {
                            this.bindSubmitOrderEvent();
                            this._addInputCartQty();
                            this._addRemoveCartEvent();
                            $(".a-agreement").each(function () {
                                //console.log();
                                $(this).magnificPopup(
                                    {
                                        items: {
                                            type: 'inline',
                                            src: "#" + $(this).attr('rel'),
                                            prependTo: _this.options.checkout.checkoutForm
                                        },
                                        fixedContentPos: true
                                        //modal: true
                                    }
                                );
                            });
                        }
                        updateItems.push(idx);
                    }
                }
                _this._updatePreviousData(updateItems);
                window.oscObserver.afterLoadingNewContent(updateItems, this._previous_data );

            }

        },

        _updatePreviousData: function (updateItems) {
            for (var idx in updateItems) {
                if (updateItems[idx] == 'payment_block') {

                    if (this._previous_data['payment']) {
                        var data = this._previous_data['payment'];
                        data.method = null;
                        this._updatePartialForm('payment', data);
                    }

                    this._updateAfterReloadPayment();

                }
            }
        },

        _updatePartialForm: function (formName, data) {
            if (data) {
                for (var idx in data) {
                    if (idx == 'method')
                        continue;
                    var paymentMethodInput = $('input[name="' + formName + '[' + idx + ']"]');
                    if (paymentMethodInput.length && data[idx] != '') {
                        paymentMethodInput.val(data[idx]);
                    }
                    var paymentMethodSelect = $('select[name="' + formName + '[' + idx + ']"]');
                    if (paymentMethodSelect.length && data[idx] != '') {
                        paymentMethodSelect.val(data[idx]);
                    }
                }
            }

        },

        _openConfirmExistEmail: function () {
            var _this = this;
            $("#confirm_dialog .content").html(_this.options.billing.checkExistsMsg);

            $("#confirm_dialog .btn_ok").click(function () {
                $("#login-email").val($("#billing\\:email").val());
                var mfp = $.magnificPopup.instance;
                mfp.items[0] =
                {
                    type: 'inline',
                    src: '#loginFrm',
                    prependTo: _this.options.checkout.checkoutForm
                }
                ;
                mfp.updateItemHTML();
                return false;

            }).find('.btn_text').html(_this.options.confirmCheckEmail.login_btn_text);

            $("#confirm_dialog .btn_cancel").click(function () {
                $.magnificPopup.close();
                _this._removeWait();
                $("#billing\\:email").focus();
            }).find('.btn_text').html(_this.options.confirmCheckEmail.cancel_btn_text);

            $.magnificPopup.open(
                {
                    items: {
                        type: 'inline',
                        src: '#confirm_dialog',
                        modal: true
                    }
                }
            );
        },

        placeOrder: function () {
            window.oscObserver.beforeSubmitOrder();

            /**
             * stop submitting order
             */
            if(window.oscObserver.stopSubmittingOrder && window.oscObserver.stopSubmittingOrder == true)
                return;

            var _this = this;
            var data = $("#checkout_form").serializeArray();
            //alert($("#braintree_nonce").val());
            //braintree_nonce
            /*
            if($("#braintree_nonce")){
                data[data.length] = {'name': 'payment[nonce]', 'value': $("#braintree_nonce").val() };
            }
            */

            var url = this.options.submitUrl;

            $.ajax({
                url: url,
                type: 'post',
                context: this,
                data: data,
                dataType: 'json',
                beforeSend: function () {
                    _this.hideErrors();
                    _this._loadWait('checkoutSteps', true);
                },

                error: function (request, status, error) {
                },
                complete: function (response) {
                    try {
                        var responseObject = $.parseJSON(response.responseText);
                        var result = responseObject.results;
                    } catch (ex) {
                        _this._removeWait();
                        return false;
                    }
                    var isError = false;
                    _this._updateHtml(responseObject);
                    _this._removeWait('checkoutSteps');


                    $("#saveOder-error").html('');
                    if(result){
                        if (result.save_order && result.save_order.error && result.save_order.error == true) {
                            //error_messages
                            //$("#saveOder-error").html(result.save_order.error_messages);
                            _this.showError('saveOder-error',result.save_order.error_messages);
                            isError = true;
                        }

                        $(".mage-error").show();
                        if (result.billing && typeof(result.billing.error) != "undefined" && result.billing.error != 0) {
                            //_this._showError("#billing-error", result.billing.message);
                            isError = true;
                            _this.showError("billing-error", result.billing.message);
                        } else {
                            $("#billing-error").html('');
                        }
                        //payment-error
                        if (result.payment && typeof(result.payment.error) != "undefined" && result.payment.error != 0) {
                            //_this._showError("#payment-error", result.payment.message);
                            _this.showError("payment-error", result.payment.message);
                            isError = true;
                        } else {
                            $("#payment-error").html('');
                        }
                        if (result.shipping_method && typeof(result.shipping_method.error) != "undefined" && result.shipping_method.error != 0) {
                            //_this._showError("#shippingmethod-error", result.shipping_method.message);
                            _this.showError("shippingmethod-error", result.shipping_method.message);
                            isError = true;
                        } else {
                            $("#shippingmethod-error").html('');
                        }

                        if (isError == true)
                            return false;
                    }


                    result = window.oscObserver.afterSaveOrder(response, result);

                    if (responseObject.success == false) {
                        if (responseObject.update_section) {
                            if (responseObject.update_section.name == 'paypaliframe') {
                                _this._removeWait('checkoutSteps');
                                $("#lbonepage-place-order-btn").hide();
                                $("#checkout-paypaliframe-load").html(responseObject.update_section.html);
                                $.magnificPopup.open(
                                    {
                                        items: {
                                            type: 'inline',
                                            src: '#checkout-paypaliframe-load',
                                            modal: true
                                        },
                                        modal: true
                                    }
                                );
                                return;
                            }
                        }
                    }

                    if (result && result.payment && result.payment.redirect) {
                        window.location = result.payment.redirect;
                        return;
                    }
                    if (result && result.save_order && result.save_order.success == true) {
                        var redirectUrl = this.options.review.successUrl;
                        if (result.save_order.redirect) {
                            redirectUrl = result.save_order.redirect;
                        }

                        window.location = redirectUrl;
                    }
                }
            });
        },

        validate: function() {
            var isValid = true;

            $("#checkout_form").validation();
            $("#checkout_form form").validation();

            $("#checkout_form").validation('clearError');

            if (!($("#checkout_form").valid('isValid'))){
                var validator = $("#checkout_form").validate();
                validator.focusInvalid();
                isValid = false;
            }
            isValid = isValid & this._validateShippingMethod();
            isValid = isValid & this._validatePaymentMethod();
            isValid = isValid & this._checkAgreements();

            return isValid;
        },

        save : function(){
            this._save();
        },

        _save: function () {
            var _this = this;
            if (this.validate()){
                var isCheckExistEmail = $("input[name='billing[create_new_account]']").is(":checked") || $("#billing\\:email").hasClass('check-email-exists');
                var checkEmailOk = true;
                if (isCheckExistEmail) {
                    checkEmailOk = false;
                    $.ajax({
                        url: _this.options.billing.checkExistsUrl,
                        type: 'POST',
                        context: this,
                        data: {email: $("#billing\\:email").val()},
                        complete: function (response) {
                            try {
                                var responseObject = $.parseJSON(response.responseText);
                                var result = responseObject.results;
                            } catch (ex) {
                                _this._removeWait();
                                return false;
                            }
                            _this._removeWait('checkoutSteps');
                            if (responseObject && responseObject.success == false) {
                                _this._openConfirmExistEmail();
                                checkEmailOk = false;
                            } else {
                                _this.placeOrder();
                                checkEmailOk = true;
                            }
                        }
                    });
                } else {
                    _this.placeOrder();
                }
                if (checkEmailOk == false)
                    return;
            } else {
            }

        }
    });

})(jQuery, window);
