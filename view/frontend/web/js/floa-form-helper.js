/**
 * 2021 Floa BANK
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    FLOA Bank
 * @copyright 2021 FLOA Bank
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

define([
    'jquery',
    'mage/url',
    'mage/translate',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/totals',
], function ($, urlBuilder, $t, checkoutData, selectPaymentMethodAction, totals) {
    'use strict';

    return function (code, country) {
        let _code;
        let _country;
        let validatorMessage = {
            'tax-code-custom': $.mage.__("Tax identification number is not valid."),
            'zip-code-custom': $.mage.__("Your birth postal code must consist of 5 digits without spaces."),
            'phone-custom': $.mage.__("Phone number is invalid."),
            'max-length-second-last-name': $.mage.__('Your second last name must not exceed 64 characters')
        };

        function initialize(code, country) {
            _code = code;
            _country = country;
        }

        function floaLog(message) {
            if (window.checkoutConfig.payment.floa.debug === true) {
                console.warn(message);
            }
        }

        function selectPaymentMethodFloa() {
            $('.payment-floa').removeClass('payment-floa-hidden');
            let method = $('.floa-payment-radio:checked').val();
            if (!method) {
                method = window.getFloaPlanSelected();
                window.floaLog('Getting floa method first: ' + method);
            }
            window.checkMethodFloa(method);
            checkoutData.setSelectedPaymentMethod(method);
            selectPaymentMethodAction({'method': method});
            if (method !== 'cbnx') {
                floaDisplayPaymentReportDateField(method);
                floaDisplayPaymentSummary(method);
            }
        }
        
        function isFloaChecked() {
            if (parseInt(window.checkoutConfig.payment.floa.plans.length) <= 0) {
                window.floaLog('no floa plan !');
                return false;
            }
            var floaCode = window.getSelectedMethodCheckout();
            window.floaLog(floaCode);
            var floaCodes = ['cbnx', 'cb1xd', 'cb3x', 'cb4x', 'cb10x'];
            if (floaCodes.indexOf(floaCode.toLowerCase()) !== -1) {
                window.floaLog('One Floa Payment is checked !');
                return true;
            }
            window.floaLog('No Floa Payment is checked !');
            return false;
        }

        function getFloaPlanSelected() {
            var method = 'cbnx';
            Object.keys(window.checkoutConfig.payment.floa.plans).forEach(function(key) {
                var planIn = window.checkoutConfig.payment.floa.plans[key];
                window.floaLog(planIn);
                if (planIn !== null && planIn.isFirstPlan === true) {
                    method = planIn.code;
                }
            });
            return method;
        }
        

        function getSelectedMethodCheckout() {
            var selectedMethodCheckout = checkoutData.getSelectedPaymentMethod();
            window.floaLog(selectedMethodCheckout);
            window.floaLog(typeof selectedMethodCheckout);
            var selectedMethod = '';
            if (typeof selectedMethodCheckout !== 'undefined' && selectedMethodCheckout !== null) {
                selectedMethod = selectedMethodCheckout.toLowerCase();
            }
            return selectedMethod;
        }

        function setPaymentMethod() {
            var method = $('.floa-payment-radio:checked').val();
            $('#form_floa_code').val(method);
            checkoutData.setSelectedPaymentMethod(method);
            window.checkMethodFloa(method);
            window.floaLog('Selecting method : ' + '#' + method);
            floaDisplayPaymentReportDateField(method);
            floaDisplayPaymentSummary(method);
        }

        function checkMethodFloa(method) {
            $('#cbnx').val(method);
            setTimeout(function() { 
                $('.floa-payment-radio:checked').removeAttr('checked');
                jQuery('#cbnx').prop('checked', true);
                jQuery('#' + method).prop('checked', true);
                if (jQuery('#fl-civility').val() === "Mrs") {
                    jQuery('.fl-payment-birthname').parent().show();
                } else {
                    jQuery('.fl-payment-birthname').parent().hide();
                }
            }, 250);
        }

        function floaDisplayPaymentReportDateField(method)
        {
            if (method === 'cb1xd') {
                $('#floa_deferred_time_block').show();
            } else if ($('#floa_deferred_time_block').is(":visible")) {
                $('#floa_deferred_time_block').hide();
            }
        }

        function floaDisplayPaymentSummary(method)
        {
            window.floaLog(method);
            $('.fl-payment-summary-panel').hide();
            $('.fl-mobile-summary-panel').addClass('payment-floa-hidden');
            $('#payment_mobile_summary_' + method).removeClass('payment-floa-hidden');
            $('#payment_summary_' + method).show();   
        }

        window.selectPaymentMethodFloa = selectPaymentMethodFloa;
        window.isFloaChecked = isFloaChecked;
        window.selectFloaMethod = setPaymentMethod;
        window.checkMethodFloa = checkMethodFloa;
        window.getSelectedMethodCheckout = getSelectedMethodCheckout;
        window.getFloaPlanSelected = getFloaPlanSelected;
        window.floaLog = floaLog;

        initialize(code, country);

        return {
            addCustomValidator: function () {
                let configs = this.getFormValidationConfig();
                configs.forEach(function (config) {
                    if (validatorMessage[config.name]) {
                        $.validator.addMethod(
                            config.name,
                            function(value, element) {
                                var regex = new RegExp(config.pattern.replace(/^\/|\/$/g, ''));
                                return regex.test(value);
                            },
                            $.mage.__(validatorMessage[config.name])
                        );
                    }
                });
            },
            getFormValidationConfig: function () {
                return window.checkoutConfig.payment.floa.formValidation || [];
            },
            getCgvLink: function () {
                return window.checkoutConfig.payment.floa.cgv_link;
            },
            getAdditionalMethod: function () {
                switch (_country) {
                    case 'FR':
                    case 'BE':
                        $(document).on('change', '#fl-civility', function () {
                            if (this.value === "Mrs") {
                                $('.fl-payment-birthname').parent().show();
                            } else {
                                $('.fl-payment-birthname').parent().hide();
                            }
                        });
                        break;
                }
            },
            getCountry: function() {
                return _country;  
            },
            isMethodSelected: function() {
                var selectedMethod = window.getSelectedMethodCheckout();
                window.floaLog('selected: ' + selectedMethod);
                window.floaLog('code: ' + _code);
                return selectedMethod == _code;
            },
            getAllPlans: function() {
                var plans = [];
                var previousTotal = window.checkoutConfig.payment.floa.total;
                var currentTotal = parseInt((totals.totals().grand_total * 100).toFixed(0));
                window.floaLog(`currentTotal: ${currentTotal}`)
                window.floaLog(`previousTotal: ${previousTotal}`)
                if (currentTotal !== previousTotal) {
                    $.ajax({
                        url: urlBuilder.build('floa/eligibility/plans') + '?' + Date.now(),
                        type: 'POST',
                        async: false,
                        dataType: 'json'
                    }).done(function(data) {
                        window.checkoutConfig.payment.floa.plans = data;
                    });
                    window.checkoutConfig.payment.floa.total = currentTotal;
                }
                window.floaLog(window.checkoutConfig.payment.floa.plans);
                var selectedMethod = window.getSelectedMethodCheckout();
                Object.keys(window.checkoutConfig.payment.floa.plans).forEach(function(key) {
                    var planIn = window.checkoutConfig.payment.floa.plans[key];
                    if (planIn !== null) {
                        planIn.isMethodSelected = ( planIn.code == selectedMethod );
                        planIn.key = key;
                        planIn.echs = parseInt(planIn.code.replace('cb', '').replace('x', '').replace('d',''));
                        plans.push(planIn);
                    }
                });
                window.floaLog(plans);
                return plans;
            },
            getSchedules: function(floaCode) {
                if (typeof window.checkoutConfig.payment.floa.plans[floaCode] !== 'undefined') {
                    return false;
                }
                var plan = window.checkoutConfig.payment.floa.plans[floaCode];
                window.floaLog(plan.schedules);
                window.floaLog(plan.schedules.length);
                return plan.schedules.length ? plan.schedules : false; 
            },
            handleEligibilityPlans: function () {
                window.floaLog(window.checkoutConfig.payment.floa.plans);
                window.floaLog(_code);
                if (typeof window.checkoutConfig.payment.floa.plans[_code] === 'undefined') {
                    return null;
                }
                window.floaLog(_code + ' loaded !');
                window.floaLog(window.checkoutConfig.payment.floa.plans[_code]);
                return window.checkoutConfig.payment.floa.plans[_code];
            },
            handlePlaceOrder: function () {
                var dataForm    = $('#floa_checkout_form');
                var ignore      = null;
                dataForm.mage('validation', {
                    ignore: ignore ? ':hidden:not(' + ignore + ')' : ':hidden'
                }).find('input:text').attr('autocomplete', 'off');
                if (dataForm.validation() && dataForm.validation('isValid')) {
                    $.ajax({
                        showLoader: true,
                        data: dataForm.serialize(),
                        url: urlBuilder.build('floa/eligibility/index'),
                        type: "POST",
                        dataType: 'json'
                    }).done(function (PaymentRequest) {
                        if (PaymentRequest.success === true) {
                            window.location.href = PaymentRequest.params['payment_url'];
                        } else {
                            $('.floa-all-payment-methods-messages').remove();
                            $('#floa_checkout_form').before(
                                '<div role="alert" class="floa-all-payment-methods-messages message message-error error">'+
                                    '<div class="scoring-failed"></div>'+
                                '</div>'
                            );
                            $.each(PaymentRequest.messages, function(i, text) {
                                $('.floa-all-payment-methods-messages .scoring-failed').append("<div>"+text+"</div>");
                            });
                        }
                    }).fail(function(e){
                        console.log(e);
                        $('.floa-all-payment-methods-messages').remove();
                        $('#floa_checkout_form').before(
                            '<div role="alert" class="' + _code + '-messages message message-error error">'+
                                '<div class="scoring-failed">'+$.mage.__("An error occured")+'</div>'+
                            '</div>'
                        );
                    });
                }
            },
        }
    }
});
