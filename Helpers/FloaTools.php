<?php
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

namespace FLOA\Payment\Helpers;

class FloaTools
{
    public const ALLOWED_LOCALE = [
        'FR' => ['fr-FR'],
        'IT' => ['it-IT'],
        'BE' => ['nl-BE', 'fr-BE'],
        'ES' => ['es-ES'],
        'PT' => ['pt-PT'],
        'DE' => ['de-DE'],
    ];

    public const DEFAULT_LOCALE = 'fr-FR';
    
    /**
     * GenerateOrderRef
     *
     * @param  mixed $method
     * @param  mixed $id
     * @return string
     */
    public function generateOrderRef($method, $id)
    {
        return $method . '_' . $id . '_D' . rand(0, 9999);
    }
    
    /**
     * ConvertToInt
     *
     * @param  mixed $amount
     * @return int
     */
    public function convertToInt($amount)
    {
        $finalAmount = $amount * 100;

        return (int) round($finalAmount);
    }
    
    /**
     * ConvertToFloat
     *
     * @param  mixed $amount
     * @return float
     */
    public function convertToFloat($amount)
    {
        $finalAmount = $amount / 100;

        return $finalAmount;
    }
    
    /**
     * ConvertToPrice
     *
     * @param  mixed $price
     * @return string
     */
    public function convertToPrice($price)
    {
        return number_format($price, 2, ',', ' ');
    }
    
    /**
     * ConvertToFloatAndPrice
     *
     * @param  mixed $amount
     * @return string
     */
    public function convertToFloatAndPrice($amount)
    {
        return number_format($amount / 100, 2, ',', ' ');
    }
    
    /**
     * GetCulture
     *
     * @param  mixed $country
     * @param  mixed $currentLocale
     * @return string
     */
    public function getCulture($country, $currentLocale)
    {
        $formattedLocale = str_replace('_', '-', $currentLocale);
        if (isset(self::ALLOWED_LOCALE[$country])) {
            if (in_array($formattedLocale, self::ALLOWED_LOCALE[$country])) {
                return $formattedLocale;
            }

            return self::ALLOWED_LOCALE[$country][0];
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * IsAllowedLocale
     *
     * @param string $country
     * @param string $locale
     *
     * @return bool
     */
    public function isAllowedLocale($country, $locale)
    {
        $formattedLocale = str_replace('_', '-', $locale);

        return isset(self::ALLOWED_LOCALE[$country]) && in_array($formattedLocale, self::ALLOWED_LOCALE[$country]);
    }
}
