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

namespace FLOA\Payment\Model\Payment;

use FLOA\Payment\Helpers\FloaTools;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FloaPayManagement;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class PaymentAbstract extends AbstractMethod
{
    /** @var bool */
    protected $_isInitializeNeeded = true;

    /** @var bool */
    protected $_isGateway = true;

    /** @var bool */
    protected $_canOrder = false;

    /** @var bool */
    protected $_canAuthorize = true;

    /** @var bool */
    protected $_canUseInternal = true;

    /** @var bool */
    protected $_canCapture = true;

    /** @var bool */
    protected $_canCapturePartial = false;

    /** @var bool */
    protected $_canCaptureOnce = false;

    /** @var bool */
    protected $_canRefund = true;

    /** @var bool */
    protected $_canRefundInvoicePartial = true;

    /** @var bool */
    protected $_canUseCheckout = true;

    /** @var string */
    protected $floaConfigCountry;

    /** @var string */
    protected $storeCountry;

    /** @var string */
    protected $currentLocale;

    /**
     * __construct
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param FloaPayManagement $floaPayManagement
     * @param StoreManagerInterface $storeManagerInterface
     * @param Resolver $resolver
     *
     * @return void
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        FloaPayManagement $floaPayManagement,
        StoreManagerInterface $storeManagerInterface,
        Resolver $resolver
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );
        $this->_scopeConfig = $scopeConfig;
        $this->_storeScope = ScopeInterface::SCOPE_STORE;
        $this->_store_id = $storeManagerInterface->getStore()->getId();
        $this->_floaPayManagement = $floaPayManagement;
        $this->currentLocale = $resolver->getLocale();
        $this->floaConfigCountry = $this->_scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, ScopeInterface::SCOPE_STORE, $this->_store_id);
        $this->storeCountry = $this->_scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE, $this->_store_id);
        $this->_floaPayManagement->initialize($this->_code, $this->_storeScope, $this->_store_id);
    }
    
    /**
     * IsAvailable
     *
     * @param  mixed $quote
     * @return void
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        $apiAvailable = false;
        $floaTools = new FloaTools();
        if ($this->_floaPayManagement->active
            && $this->_floaPayManagement->contextCheck['state'] == true
            && $quote->getGrandTotal() <= $this->_floaPayManagement->maxAmount
            && $quote->getGrandTotal() >= $this->_floaPayManagement->minAmount
            && $quote->getBillingAddress()->getCountryId() == $this->floaConfigCountry
        ) {
            $apiAvailable = true;
        }

        return $apiAvailable && parent::isAvailable($quote);
    }
}
