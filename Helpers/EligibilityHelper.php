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

use FLOA\Payment\Helpers\MobileDetect;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class EligibilityHelper
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress */
    private $remoteAddress;

    /** @var \Magento\Framework\App\ObjectManager */
    private $objectManager;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /**
     * Construct
     *
     * @param RemoteAddress $remoteAddress
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     *
     * @return void
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Customer
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @param array $eligibilityDatas
     *
     * @return array
     */
    public function customer($billingAddress, $eligibilityDatas)
    {
        $orderDates = $this->_getMinAndMaxOrderDate($eligibilityDatas['customer_id']);

        return [
            'civility' => $eligibilityDatas['score_civility'],
            'lastName' => $billingAddress->getLastname(),
            'firstName' => $billingAddress->getFirstname(),
            'cellPhoneNumber' => $eligibilityDatas['score_phone'],
            'birthName' => $eligibilityDatas['score_maidenname'],
            'SecondLastName' => $eligibilityDatas['score_secondlastname'],
            'reference' => $eligibilityDatas['customer_id'],
            'birthDate' => $eligibilityDatas['score_ddn'],
            'birthZipCode' => $eligibilityDatas['score_cp'],
            'email' => $eligibilityDatas['customer_email'],
            'homeAddress' => [
                'line1' => $billingAddress->getStreet()[0],
                'zipCode' => $billingAddress->getPostcode(),
                'city' => $billingAddress->getCity(),
                'countryCode' => $billingAddress->getCountryId(),
            ],
            'history' => [
                'firstOrderDate' => $orderDates['first'],
                'lastOrderDate' => $orderDates['last'],
            ],
            'trustLevel' => 'Standard',
            'ipAddress' => $this->remoteAddress->getRemoteAddress() != '::1' ? $this->remoteAddress->getRemoteAddress() : '127.0.0.1',
            'nationalId' => $eligibilityDatas['nationalId'],
        ];
    }

    /**
     * ShoppingCarts
     *
     * @param mixed $cart
     * @param mixed $shippingAddress
     * @param mixed $orderRef
     *
     * @return void
     */
    public function shoppingCarts($cart, $shippingAddress, $orderRef)
    {
        $productsList = $cart->getAllVisibleItems();
        $products = [];
        $shippingMethod = self::_getShippingMethod(
            $shippingAddress->getShippingMethod(),
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        $productsCount = 0;
        $floaTools = new FloaTools();
        if (isset($productsList) && count($productsList) > 0) {
            foreach ($productsList as $product) {
                $productInfo = $this->objectManager->create(\Magento\Catalog\Model\Product::class)->load($product['product_id']);
                $cats = $productInfo->getCategoryIds();
                $_cat = is_array($cats) && !empty($cats) ? $cats[0] : null;
                $_category = null;
                $_parent_category = null;
                $manufacturer = empty($product->getAttributeText('manufacturer')) ? '' : $product->getAttributeText('manufacturer');
                if ($_cat) {
                    $_category = $this->objectManager->create(\Magento\Catalog\Api\CategoryRepositoryInterface::class)->get($_cat);
                    $_parent_category = ($_category) ? $_category->getParentCategory() : false;
                }
                $products[] = [
                    'name' => $product->getName(),
                    'rawAmount' => $floaTools->convertToInt($product->getRowTotal()),
                    'categories' => [
                        [
                            'name' => ($_parent_category) ? $_parent_category->getName() : '',
                            'parent' => '',
                        ],
                        [
                            'name' => ($_category) ? $_category->getName() : '',
                            'parent' => ($_parent_category) ? $_parent_category->getName() : '',
                        ],
                        [
                            'name' => $manufacturer,
                        ],
                    ],
                    'shipping' => [
                        'method' => $shippingMethod,
                        'delayInDays' => 0,
                        'address' => [
                            'line1' => $shippingAddress->getStreet()[0],
                            'zipCode' => $shippingAddress->getPostcode(),
                            'city' => $shippingAddress->getCity(),
                            'countryCode' => $shippingAddress->getCountryId(),
                        ],
                    ],
                ];
                $productsCount += $product->getQty();
            }
        }

        return [
            [
                'reference' => $orderRef,
                'rawAmount' => $floaTools->convertToInt($cart->getGrandTotal()),
                'productsCount' => (int) $productsCount,
                'products' => $products,
            ],
        ];
    }

    /**
     * SaleChannel
     *
     * @return void
     */
    public function saleChannel()
    {
        try {
            $mobile_detect = new MobileDetect(null, null);
            if ($mobile_detect->isTablet()) {
                if ($mobile_detect->is('ipad')) {
                    $result = 'Ipad';
                } else {
                    $result = 'Tablet';
                }
            } elseif ($mobile_detect->isMobile()) {
                if ($mobile_detect->is('iphone')) {
                    $result = 'IPhone';
                } elseif ($mobile_detect->is('androidos')) {
                    $result = 'AndroidSmartPhone';
                } else {
                    $result = 'SmartPhone';
                }
            }
        } catch (\Exception $e) {
            $result = 'Desktop';
        }

        return isset($result) ? $result : 'Desktop';
    }

    /**
     * MerchantSite
     *
     * @param mixed $cart
     *
     * @return void
     */
    public function merchantSite($cart)
    {
        $suffix = '';
        if (version_compare($this->productMetadata->getVersion(), '2.3.0', '<')) {
            $suffix = 'old';
        }

        return [
            'homeUrl' => $this->storeManager->getStore()->getBaseUrl(),
            'backUrl' => $this->storeManager->getStore()->getUrl(
                'floa/back/index',
                ['_nosid' => true]
            ),
            'returnUrl' => $this->storeManager->getStore()->getUrl(
                'floa/payment/validate' . $suffix,
                ['_nosid' => true]
            ),
            'notificationUrl' => $this->storeManager->getStore()->getUrl(
                'floa/payment/ipn' . $suffix,
                ['_nosid' => true]
            ),
        ];
    }

    /**
     * Address
     *
     * @param mixed $address
     *
     * @return void
     */
    public function address($address)
    {
        return [
            'line1' => $address->getStreet()[0],
            'zipCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'countryCode' => $address->getCountryId(),
        ];
    }

    /**
     * GetMinAndMaxOrderDate
     *
     * @param mixed $customerId
     *
     * @return void
     */
    private function _getMinAndMaxOrderDate($customerId)
    {
        $first = '1970-01-01';
        $last = '1970-01-01';
        $customerOrders = $this->objectManager->create(\Magento\Sales\Model\Order::class)
            ->getCollection()
            ->addAttributeToFilter('customer_id', $customerId)
        ;
        if (count($customerOrders) > 0) {
            $dateFormat = 'Y-m-d';
            $first = date($dateFormat, strtotime($customerOrders->getFirstItem()->getCreatedAt()));
            $last = date($dateFormat, strtotime($customerOrders->getLastItem()->getCreatedAt()));
        }

        return [
            'first' => $first,
            'last' => $last,
        ];
    }

    /**
     * GetShippingMethod
     *
     * @param mixed $methodCode
     * @param mixed $storeScope
     * @param mixed $storeId
     *
     * @return void
     */
    private function _getShippingMethod($methodCode, $storeScope, $storeId)
    {
        $responseCode = 'STD';
        $scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        if ($carrier = explode('_', $methodCode)[0]) {
            $deliveryMethodsMap = $scopeConfig->getValue('floa/delivery_methods/mapping', $storeScope, $storeId);
            $mappingList = json_decode($deliveryMethodsMap ? $deliveryMethodsMap : '[]', true);
            if (is_array($mappingList) && !empty($mappingList)) {
                foreach ($mappingList as $mapping) {
                    if ($mapping['carrier_name'] == $carrier && $mapping['mapping_code'] != '') {
                        $responseCode = $mapping['mapping_code'];
                    }
                }
            }
        }

        return $responseCode;
    }
}
