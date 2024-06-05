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

namespace FLOA\Payment\Model\FormValidation;

use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Validator
{
    public const ALL_PM_CODE = 'all';

    public const SELECT_TYPE = 'select';

    public const REGEX_DATE = "/^((((19|[2-9]\d)\d{2})\-(0[13578]|1[02])\-" .
        "(0[1-9]|[12]\d|3[01]))|(((19|[2-9]\d)\d{2})\-(0[13456" .
        "789]|1[012])\-(0[1-9]|[12]\d|30))|(((19|[2-9]\d)\d{2})" .
        "\-02\-(0[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468]" .
        "[048]|[13579][26])|((16|[2468][048]|[3579][26])00))\-02\-29))$/";

    public const INTERNATIONAL_ZIP = '99';

    public const LASTNAME_REGEX = "/^[a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]+(([',. -][a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ ])?[a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]*)*$/";

    public const PHONE_REGEX = [
        'FR' => "/^(?:(?:\+|00)(33|95[046])[\s.-]{0,3}(?:\(0\)[\s.-]{0,3})?|0)[1-9](?:(?:[\s.-]?\d{2}){4}|\d{2}(?:[\s.-]?\d{3}){2})$/",
        'IT' => "/^((?:0039)?|(?:\+39)?)[ ]?(6\d{2}|7[1-9]\d{1})[ ]?\d{3}[ ]?\d{4}$/",
        'BE' => "/^(((\+|00)32[ ]?(?:\(0\)[ ]?)?)|0){1}(4(60|[789]\d)\/?(\s?\d{2}\.?){2}(\s?\d{4})|(\d\/?\s?\d{4}|\d{3}\/?\s?\d{2})(\.?\s?\d{2}){2})$/",
        'PT' => "/^((\+351|00351|351)?)[ ]?(2\d{1}|(9(3|6|2|1)))\d{1}([ ]?\d{3}){2}$/",
        'ES' => "/^(([+]|00)34)?[ ]?(6\d{2}|7[1-9]\d{1})[ ]?([ ]?\d{2}){3}$/",
        'default' => "/^([+])?\d{9,13}$/",
    ];

    public const CP_REGEX = [
        'FR' => "/^\d{2}|^\d{3}$|^\d{5}$/",
        'IT' => "/^99$|^\d{3}$|^\d{5}$/",
        'BE' => "/^99$|^\d{4}$/",
        'PT' => "/^99$|^\d{4}-\d{3}$/",
        'ES' => "/^99$|^\d{5}$/",
    ];

    public const NIF_REGEX = [
        'IT' => '/^([a-zA-Z]{6})+([0-9]{2})+([a-zA-Z]{1})+([0-9]{2})+([a-zA-Z]{1})+([0-9]{3})+([a-zA-Z]{1})$/',
        'PT' => '/^[0-9]{9}$/',
        'ES' => '/^([a-z]|[A-Z]|[0-9])[0-9]{7}([a-z]|[A-Z]|[0-9])$/',
    ];

    /** @var Config */
    protected $config;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var string[] */
    protected $errors = [];

    /** @var mixed */
    protected $requestData;

    /** @var mixed */
    protected $paymentCode;

    /** @var \Magento\Quote\Api\Data\AddressInterface */
    protected $billingAddress;
    
    /**
     * Construct
     *
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     *
     * @return void
     */
    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config->getFormConfiguration();
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }
    
    /**
     * Validate
     *
     * @param  mixed $data
     * @param  mixed $paymentCode
     * @return void
     */
    public function validate($data, $paymentCode)
    {
        $this->requestData = $data;
        $this->paymentCode = $paymentCode;
        $countryCode = $this->scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
        $configuration = isset($this->config[$countryCode]) ? $this->config[$countryCode] : [];

        foreach ($data as $key => $value) {
            if (isset($configuration[$key])) {
                $fieldConfig = $configuration[$key];
                $configCode = $this->getAttribute($fieldConfig, 'payment_code');
                if ($configCode === $this->paymentCode || $configCode === self::ALL_PM_CODE) {
                    $this->validateRequiredField($fieldConfig, $value);
                    $this->validateOptions($fieldConfig, $value);
                    $this->validateUsingPattern($fieldConfig, $value);
                    $this->validateUsingClassMethod($fieldConfig, $value);
                }
            }
        }
    }
    
    /**
     * ValidateRequiredField
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function validateRequiredField($fieldConfig, $fieldValue)
    {
        $required = $this->getValue($fieldConfig, 'required') == 'true' ? true : false;
        if ($required && !$fieldValue) {
            $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
        } elseif (null !== $requiredIf = $this->getValue($fieldConfig, 'required-if')) {
            $value = $this->getAttribute($requiredIf, 'value');
            $field = $this->getAttribute($requiredIf, 'field');
            $isOptional = $this->getAttribute($requiredIf, 'optional');

            if ($value == $this->requestData[$field] && !$fieldValue && !$isOptional) {
                $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
            }
        }
    }
    
    /**
     * ValidateOptions
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function validateOptions($fieldConfig, $fieldValue)
    {
        if ($this->getAttribute($fieldConfig, 'type') === self::SELECT_TYPE) {
            $select = $this->getValue($fieldConfig, 'choose');

            $options = [];
            foreach ($select['option'] as $option) {
                $options[] = $this->getAttribute($option, 'value');
            }

            if (!in_array($fieldValue, $options)) {
                $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
            }
        }
    }
    
    /**
     * ValidateUsingPattern
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function validateUsingPattern($fieldConfig, $fieldValue)
    {
        $pattern = $this->getValue($fieldConfig, 'pattern');
        if ($pattern && !preg_match($pattern, $fieldValue) && $fieldValue && !$this->getValue($fieldConfig, 'required-if')) {
            $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
        }
    }
    
    /**
     * ValidateUsingClassMethod
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function validateUsingClassMethod($fieldConfig, $fieldValue)
    {
        $method = $this->getValue($fieldConfig, 'validator-method');
        if ($method && method_exists($this, $method)) {
            $this->$method($fieldConfig, $fieldValue);
        }
    }
    
    /**
     * GetValue
     *
     * @param  mixed $config
     * @param  mixed $key
     * @return void
     */
    protected function getValue($config, $key)
    {
        return isset($config['_value'][$key]) ? $config['_value'][$key] : null;
    }
    
    /**
     * GetAttribute
     *
     * @param  mixed $config
     * @param  mixed $key
     * @return void
     */
    protected function getAttribute($config, $key)
    {
        return isset($config['_attribute'][$key]) ? $config['_attribute'][$key] : null;
    }

    /**
     * IsValidMaidenName
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function IsValidMaidenName($fieldConfig, $fieldValue)
    {
        if (empty($fieldValue) || !preg_match(self::LASTNAME_REGEX, $fieldValue)) {
            $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
        }
    }
    
    /**
     * IsPaymentDateValid
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    public function isPaymentDateValid($fieldConfig, $fieldValue)
    {
        if (($this->paymentCode == 'cb1xd'
            && $this->scopeConfig->getValue(FloaScopeConfigInterface::REPORT_MODE, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId()) == 1)
            && (
                !$this->isValidDate($fieldValue)
                || $fieldValue > date('Y-m-d', strtotime('+30days'))
                || $fieldValue < date('Y-m-d')
            )) {
            $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
        }
    }
    
    /**
     * IsMajorCustomer
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function isMajorCustomer($fieldConfig, $fieldValue)
    {
        if (!$this->isValidDate($fieldValue)) {
            $this->addError(__($this->getValue($fieldConfig, 'invalid-message'))->render());
        } else {
            $today = new \DateTime();
            $scoreDdnObject = new \DateTime($fieldValue);
            if ($scoreDdnObject->diff($today)->y < 18) {
                $this->addError(__('You must be over 18 years old.')->render());
            }
        }
    }
    
    /**
     * IsEsValidIdentificationNumber
     *
     * @param  mixed $fieldConfig
     * @param  mixed $fieldValue
     * @return void
     */
    protected function isEsValidIdentificationNumber($fieldConfig, $fieldValue)
    {
        if (!isset($this->requestData['birth_zip'])) {
            $this->addError(__('Your birth postal code must consist of 5 digits without spaces.')->render());
        }
        if (!$this->billingAddress) {
            $this->addError(__('Check your billing address.'));
        }
        if (isset($this->requestData['birth_zip']) && $this->billingAddress) {
            $birthZip = $this->requestData['birth_zip'];
            $errorMessage = $this->getValue($fieldConfig, 'invalid-message');
            $this->validateEsIdentificationNumber($fieldValue, $birthZip, $this->billingAddress->getPostcode(), $errorMessage);
        }
    }

    /**
     * ValidateEsIdentificationNumber
     *
     * @param string $identificationNumber
     * @param string $birthZip
     * @param string $billingZip
     * @param string $errorMessage
     *
     * @return void
     */
    protected function validateEsIdentificationNumber($identificationNumber, $birthZip, $billingZip, $errorMessage)
    {
        if (preg_match('/^K[0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            $this->addError(__($errorMessage)->render());
        } elseif (preg_match('/^L[0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            if ($billingZip != self::INTERNATIONAL_ZIP || $birthZip == self::INTERNATIONAL_ZIP) {
                $this->addError(__($errorMessage)->render());
            }
        } elseif (preg_match('/^[0-9]{8}[A-Z]{1}$|^[ILMOTXYZ][0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            if ($billingZip == self::INTERNATIONAL_ZIP) {
                $this->addError(__($errorMessage)->render());
            }
        } else {
            $this->addError(__($errorMessage)->render());
        }
    }
    
    /**
     * IsValidDate
     *
     * @param  mixed $date
     * @return void
     */
    protected function isValidDate($date)
    {
        if (preg_match(self::REGEX_DATE, $date)) {
            return true;
        }

        return false;
    }
    
    /**
     * AddError
     *
     * @param  mixed $error
     * @return void
     */
    protected function addError($error)
    {
        if (!in_array($error, $this->errors)) {
            $this->errors[] = $error;
        }
    }
    
    /**
     * GetErrors
     *
     * @return void
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * SetBillingAddress
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     *
     * @return void
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }
}
