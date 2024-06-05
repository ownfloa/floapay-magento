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

use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PaymentHelper
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Magento\Framework\Locale\Resolver */
    private $resolver;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;
    
    /**
     * Construct
     *
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $resolver
     *
     * @return void
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Resolver $resolver
    ) {
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resolver = $resolver;
    }
    
    /**
     * Customer
     *
     * @param  mixed $billingAddress
     * @param  mixed $shippingAddress
     * @param  mixed $customerId
     * @return array
     */
    public function customer($billingAddress, $shippingAddress, $customerId)
    {
        return [
            'billingAddress' => [
                'city' => $billingAddress->getCity(),
                'line1' => $billingAddress->getStreet()[0],
                'name' => 'Billing address',
                'zipCode' => $billingAddress->getPostcode(),
            ],
             'deliveryAddress' => [
                'city' => $shippingAddress->getCity(),
                'line1' => $shippingAddress->getStreet()[0],
                'name' => 'Shipping address',
                'zipCode' => $shippingAddress->getPostcode(),
             ],
             'customerRef' => $customerId,
             'country' => strtoupper($this->scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId())),
        ];
    }
    
    /**
     * Configuration
     *
     * @param  mixed $cart
     * @param  mixed $paymentOptionRef
     * @param  mixed $reportDelayInDays
     * @return array
     */
    public function configuration($cart, $paymentOptionRef, $reportDelayInDays = 0)
    {
        $suffix = '';
        if (version_compare($this->productMetadata->getVersion(), "2.3.0", '<')) {
            $suffix = 'old';
        }
        return [
            "culture"                   => $this->getCulture(),
            "merchantHomeUrl"           => $this->storeManager->getStore()->getBaseUrl(),
            "merchantBackUrl"           => $this->storeManager->getStore()->getUrl(
                'floa/back/index',
                ['_nosid' => true]
            ),
            "merchantReturnUrl"         => $this->storeManager->getStore()->getUrl(
                'floa/payment/validate' . $suffix,
                ['_nosid' => true]
            ),
            "notificationUrl"   => $this->storeManager->getStore()->getUrl(
                'floa/payment/ipn' . $suffix,
                ['_nosid' => true]
            ),
            "paymentOptionRef"          => $paymentOptionRef,
            "formType"                  => "default",
            "reportDelayInDays"         => $reportDelayInDays,
            "country"                   => strtoupper(
                $this->scopeConfig->getValue(
                    FloaScopeConfigInterface::COUNTRY_CODE_PATH,
                    ScopeInterface::SCOPE_STORE,
                    $this->storeManager->getStore()->getId()
                )
            ),
        ];
    }
    
    /**
     * GetCulture
     *
     * @return string
     */
    private function getCulture()
    {
        $country = $this->scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
        $floaTools = new FloaTools();

        return $floaTools->getCulture($country, $this->resolver->getLocale());
    }
    
    /**
     * OrderData
     *
     * @param  mixed $shippingAddress
     * @param  mixed $scoringDatas
     * @param  mixed $cart
     * @return array
     */
    public function orderData($shippingAddress, $scoringDatas, $cart)
    {
        $floaTools = new FloaTools();
        return [
            'shippingAddress' => [
                'city' => $shippingAddress->getCity(),
                'line1' => $shippingAddress->getStreet()[0],
                'name' => 'Shipping address',
                'zipCode' => $shippingAddress->getPostcode(),
            ],
            'orderFeesAmount' => $scoringDatas['totalAmount'] - $floaTools->convertToInt($cart->getGrandTotal()),
            'orderRowsAmount' => $floaTools->convertToInt($cart->getGrandTotal()) - $floaTools->convertToInt($cart->getShippingAddress()->getShippingAmount()),
            'orderShippingAmount' => $floaTools->convertToInt($cart->getShippingAddress()->getShippingAmount()),
            'orderSummaryRef' => $scoringDatas['orderRef'],
            'orderRef' => $scoringDatas['orderRef'],
            'amount' => $scoringDatas['totalAmount'],
            'scoringToken' => $scoringDatas['token'],
        ];
    }
}
