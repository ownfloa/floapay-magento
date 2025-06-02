<?php
/**
 * 2022 Floa BANK
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
 * @author    Floa Bank
 * @copyright 2023 Floa Bank
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace FLOA\Payment\Model\Config;

interface FloaScopeConfigInterface
{
    public const IS_ACTIVE_PATH = 'payment/floa_payment/active';
    public const COUNTRY_CODE_PATH = 'payment/floa_payment/country';
    public const WIDGET_PRODUCT_PATH = 'payment/floa_payment/widget_product';
    public const WIDGET_CART_PATH = 'payment/floa_payment/widget_cart';
    public const DEF_TYPE_PATH = 'payment/floa_payment/cb1xd_report_mode';
    public const DEF_DELAY_PATH = 'payment/floa_payment/cb1xd_report_delay';
    public const REPORT_MODE = 'payment/floa_payment/cb1xd_report_mode';
    public const REPORT_DELAY = 'payment/floa_payment/cb1xd_report_delay';
    public const CGV_URL = 'payment/floa_payment/cgv_url';
    public const DEBUGMODE = 'payment/floa_payment/debug';
    public const ENV = 'payment/floa_payment/env';
    public const TOKEN = 'payment/floa_payment/token';
    public const TOKEN_ENV = 'payment/floa_payment/token_env';
    public const TOKEN_UPDATE = 'payment/floa_payment/token_update';
    public const MERCHANT_ID = 'payment/floa_payment/merchant_id';
    public const MERCHANT_LOGIN = 'payment/floa_payment/merchant_login';
    public const MERCHANT_PWD = 'payment/floa_payment/merchant_password';
    public const TIMEOUT_SCHEDULES = 'payment/floa_payment/timeout_schedules';
    public const TIMEOUT_PAYMENTS = 'payment/floa_payment/timeout_payments';
    public const TIMEOUT_CONNECT = 'payment/floa_payment/timeout_connect';
}
