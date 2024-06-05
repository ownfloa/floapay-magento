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

define(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'mage/translate',
        'mage/url',
        'FLOA_Payment/js/floa-form-helper',
        'mage/validation'
    ],
    function ($, modal, _, Component, $t, urlBuilder, floaFormHelper) {
        'use strict';
        let countryCode = window.checkoutConfig.payment.floa.countryCode;
        let paymentCode = 'cb10x';

        let formHelper = floaFormHelper(paymentCode, countryCode);
        formHelper.addCustomValidator();
        formHelper.getAdditionalMethod();

        return Component.extend({
            defaults: {
                template: 'FLOA_Payment/payment/index',
                EligibilityPlan : null
            },
            getEchs: function() {
                return 10;
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'EligibilityPlan'
                    ]);
                return this;
            },
            initialize: function () {
                var self = this;
                this._super();
                self.EligibilityPlan = formHelper.handleEligibilityPlans();
                return this;
            },
            getCountry: function() {
                return formHelper.getCountry();  
            },
            isMethodSelected: function() {
                return formHelper.isMethodSelected();
            },
            isActive: function () {
                return true;
            },
            getPlan: function() {
                return this.EligibilityPlan;
            },
            getAllPlans: function() {
                return formHelper.getAllPlans();
            },
            getCgvLink: function () {
                return formHelper.getCgvLink();
            },
            isFirstPlan: function() {
                return this.EligibilityPlan !== null ? this.EligibilityPlan.isFirstPlan : false;
            },
            isLastPlan: function() {
                return this.EligibilityPlan.isLastPlan;
            },
            isFloaChecked: function() {
                return window.isFloaChecked();
            },
            getMainTitle: function() {
                return window.checkoutConfig.payment.floa.mainTitle;
            },
            selectPaymentMethodFloa: function() {
                window.selectPaymentMethodFloa();
            },
            placeOrder: function () {
                formHelper.handlePlaceOrder();
            }
        });
    }
);
