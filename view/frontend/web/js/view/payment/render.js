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
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'cb3x',
                component: 'FLOA_Payment/js/view/payment/method-renderer/cb3x'
            },
            {
                type: 'cb4x',
                component: 'FLOA_Payment/js/view/payment/method-renderer/cb4x'
            },
            {
                type: 'cb10x',
                component: 'FLOA_Payment/js/view/payment/method-renderer/cb10x'
            },
            {
                type: 'cb1xd',
                component: 'FLOA_Payment/js/view/payment/method-renderer/cb1xd'
            }
        );
        return Component.extend({});
    });