/*
 Lotus Breath - One Step Checkout
 Copyright (C) 2014  Lotus Breath
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

(function ($, window) {
    'use strict';
    /**
     * Review and place order
     */
    $.widget('lotusbreath.onestepcheckout', $.lotusbreath.onestepcheckout, {
        options: {
            process: {
                shipping_billing : {
                    tasks: 'billing,shipping',
                    relative_parts : ['shipping_block', 'payment_block', 'review_block'],
                    completed_observers : ['afterUpdatingBilling','afterUpdateShipping']
                },
                shipping : {
                    tasks: 'shipping',
                    relative_parts : ['shipping_block', 'review_block'],
                    completed_observers : ['afterUpdateShipping']
                },
                billing : {
                    tasks: 'billing',
                    relative_parts : ['payment_block', 'review_block'],
                    completed_observers : ['afterUpdatingBilling']
                },
                delivery : {
                    tasks: 'delivery',
                    relative_parts : ['review_block'],
                    completed_observers : ['afterUpdateShippingMethod']
                },
                payment : {
                    tasks : 'payment',
                    relative_parts : ['review_block'],
                    completed_observers : ['afterUpdatePaymentMethod']
                }
            },
            activityUrl : '/onestepcheckout/index/save'
        },
        _create: function () {
            this._super();
            var _this = this;
        },
        executeProcess: function(processName){
            var _this = this;
            var params = $("#checkout_form").serializeArray();

            var process = _this.options.process[processName];
            params[params.length] = {'name' : 'task', 'value': process.tasks};
            params[params.length] = {'name' : 'relative_parts', 'value': process.relative_parts};


            this.queueProcess(processName,{
                url: _this.options.activityUrl,
                type: 'POST',
                data: params,
                //async : false,
                beforeSend: function () {
                    process.relative_parts.forEach(
                        function(block){
                            _this._loadWait(block);
                        }
                    );

                },
                complete: function (response) {
                    try {
                        var responseObject = $.parseJSON(response.responseText);
                        _this._updateHtml(responseObject);
                        //run observers
                        process.completed_observers.forEach(
                            function(observer){
                                window.oscObserver.fire(observer, responseObject);
                            }
                        );
                        if(responseObject.results.done_parts){
                            responseObject.results.done_parts.forEach(function(part){
                                if($("."+part)){
                                    //$("."+part).addClass('step-passed');
                                }
                            });

                        }
                        //console.log($(".part-address"));
                        //$(".part-address").addClass('done');

                    } catch (ex) {
                        console.log(ex);
                    }
                },
                error: function () {

                }

            });
        }


    });
})(jQuery, window);
