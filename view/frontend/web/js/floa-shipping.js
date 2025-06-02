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
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data'
    ], function ($, selectPaymentMethodAction, checkoutData) {
        'use strict';

        return function (originalComponent) {
            return originalComponent.extend({
                setShippingInformation: function () {
                    var _this = this;

                    this.diableSelection();
                    _this._super();
                },

                diableSelection: function () {
                    // Disable payment selection to reload infos
                    $('#cbnx').val('');
                    $('.floa-payment-radio:checked').removeAttr('checked');
                    jQuery('#cbnx').prop('checked', false);
                    var checkoutPaymentMethod = checkoutData.getSelectedPaymentMethod();
                    var floaCodes = ['cbnx', 'cb1xd', 'cb3x', 'cb4x', 'cb10x'];
                    if (typeof checkoutPaymentMethod !== 'string' || checkoutPaymentMethod === null)  {
                        return;
                    }
                    if (floaCodes.indexOf(checkoutPaymentMethod.toLowerCase()) === -1) {
                        return;
                    }
                    checkoutData.setSelectedPaymentMethod(null);
                    jQuery('#' + checkoutPaymentMethod).prop('checked', false);
                },
            });
        };
    }
);
