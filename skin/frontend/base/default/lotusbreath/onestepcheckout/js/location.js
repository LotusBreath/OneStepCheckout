(function ($, window) {
    'use strict';
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {

            options:{
                location: {
                    doesUpdateLocation : true,
                }
            },
            _create: function () {
                this._super();
            },
            /*public call*/
            updateLocation: function(){
                this._updateLocation();
            },
            doesUpdateLocation : function(){
                return this.options.location.doesUpdateLocation;
            },
            setDoesUpdateLocation: function(flag){
                this.options.location.doesUpdateLocation = flag;
                //console.log('Set:'+this.options.location.doesUpdateLocation);
                //console.log(flag);
            },
            /* Call when update location of address that cause to change shipping rates, shipping methods ,or payment  */
            _updateLocation: function (data, typeUpdate) {
                //console.log('call:'+this.options.location.doesUpdateLocation);
                if(this.doesUpdateLocation() == false){
                    return;
                }
                if (!typeUpdate)
                    typeUpdate = 'shipping';
                var _this = this;
                //var params = $("#checkout_form").serializeArray();
                if (typeUpdate == 'billing') {
                    //params[params.length] = {'name': 'step', 'value': 'update_location_billing'};
                    //params[params.length] = {'name' : 'task', 'value': 'billing'};
                    window.oscObserver.beforeUpdateBilling();
                    window.oscObserver.beforeUpdateShipping();
                    _this.executeProcess('billing');

                } else {
                    if (typeUpdate == 'billing_shipping') {
                        window.oscObserver.beforeUpdateBilling();
                        window.oscObserver.beforeUpdateShipping();
                        //params[params.length] = {'name': 'step', 'value': 'update_location_billing_shipping'};
                        //params[params.length] = {'name' : 'task', 'value': 'billing,shipping'}
                        _this.executeProcess('shipping_billing');
                    } else {
                        oscObserver.beforeUpdateShipping();
                        //params[params.length] = {'name': 'step', 'value': 'update_location'};
                        //params[params.length] = {'name' : 'task', 'value': 'shipping'};
                        _this.executeProcess('shipping');
                    }
                }
                //if (_this.isSavingAddress)
                    //return;


            }
        }
    )
})(jQuery, window);