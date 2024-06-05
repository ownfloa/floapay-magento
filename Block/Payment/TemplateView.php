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

namespace FLOA\Payment\Block\Payment;

use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FloaPayConfigProvider;
use FLOA\Payment\Model\FormValidation\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class TemplateView extends Template implements BlockInterface
{
    public const BOTTOM_FIELDS = [
        'accept_data_collect',
        'accept_terms_conditions',
    ];

    public const BOOLEAN_FILED = [
        'required',
    ];

    /** @var Config */
    protected $formConfig;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var CheckoutSession $checkoutSession */
    protected $checkoutSession;

    /** @var PriceCurrencyInterface $priceCurrency */
    protected $priceCurrency;
    
    /**
     * Construct
     * @param Template\Context $context
     * @param Config $formConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @return void
     */
    public function __construct(
        Template\Context $context,
        Config $formConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->formConfig = $formConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }
    
    /**
     * GetFormConfiguration
     *
     * @return void
     */
    public function getFormConfiguration()
    {
        $country = $this->getCountryCode();
        return $this->formConfig->getFormConfig($country);
    }
    
    /**
     * IsBottomField
     *
     * @param  mixed $field
     * @return bool
     */
    public function isBottomField($field)
    {
        return in_array($field, self::BOTTOM_FIELDS);
    }
    
    /**
     * GetConfigValue
     *
     * @param  mixed $config
     * @param  mixed $key
     * @return mixed
     */
    public function getConfigValue($config, $key)
    {
        if (in_array($key, self::BOOLEAN_FILED)) {
            return isset($config['_value'][$key]) ? filter_var($config['_value'][$key], FILTER_VALIDATE_BOOLEAN) : false;
        }
        return isset($config['_value'][$key]) ? $config['_value'][$key] : null;
    }
    
    /**
     * GetConfigAttribute
     *
     * @param  mixed $config
     * @param  mixed $key
     * @return mixed
     */
    public function getConfigAttribute($config, $key)
    {
        return isset($config['_attribute'][$key]) ? $config['_attribute'][$key] : null;
    }

    /**
     * Function getFormatedPrice
     *
     * @param float $price
     *
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    /**
     * Get country code
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Function getCgvLink
     *
     * @return string
     */
    public function getCgvLink()
    {
        $cgvUrls = FloaPayConfigProvider::CGV_LINKS;
        $countryCode = $this->getCountryCode() ?? 'default';

        return $cgvUrls[$countryCode];
    }

    /**
     * Function getPrivacyPolicyLink
     *
     * @return string
     */
    public function getPrivacyPolicyLink()
    {
        $privacyPolicyUrls = FloaPayConfigProvider::PRIVACY_POLICY_LINKS;
        $countryCode = $this->getCountryCode() ?? 'default';

        return $privacyPolicyUrls[$countryCode];
    }

    /**
     * GetCustomerPhone
     *
     * @return string
     */
    public function getCustomerTelephone()
    {
        return $this->checkoutSession->getQuote()->getBillingAddress()->getTelephone();
    }

    /**
     * Check if customer phone match pattern
     * Allow to display or not on final form
     */
    public function validateCustomerPhone()
    {
        $phone = $this->getCustomerTelephone();
        $pattern = '/^([+])?\d{9,13}$/';
        $isMatching = preg_match($pattern, $phone);
        
        return (bool) $isMatching === false ? false : true;
    }

    /**
     * GetCustomerGender
     *
     * @return string
     */
    public function getCustomerGender()
    {
        $genderId = $this->checkoutSession->getQuote()->getCustomer()->getGender();
        switch ($genderId) {
            case 1 :
                $gender = 'Mr';
                break;
            case 2 :
                $gender = 'Mrs';
                break;
            default:
                $gender = '';
        }
        return $gender;
    }

    /**
     * GetCustomerNif
     *
     * @return string
     */
    public function getCustomerNif()
    {
        return $this->checkoutSession->getQuote()->getCustomer()->getTaxvat();
    }
}
