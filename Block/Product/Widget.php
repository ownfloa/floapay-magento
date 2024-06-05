<?php
/**
 * Copyright since 2023 Floa Bank
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author 202 ecommerce <tech@202-ecommerce.com>
 * @copyright 2022 Floa
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace FLOA\Payment\Block\Product;

use FLOA\Payment\Helpers\EligibilityHelper;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FloaPayLogger;
use FLOA\Payment\Model\FloaPayManagement;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class Widget extends View
{
    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var string|int
     */
    protected $storeCode;

    /**
     * @var EligibilityHelper
     */
    protected $eligibilityHelper;

    /**
     * @var FloaPayManagement
     */
    protected $floaPayManagement;

    /**
     * @var Repository
     */
    protected $assetRepository;

    /**
     * @var array
     */
    protected $offers;

    /**
     * Widget constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param EligibilityHelper $eligibilityHelper
     * @param FloaPayManagement $floaPayManagement
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        EligibilityHelper $eligibilityHelper,
        FloaPayManagement $floaPayManagement,
        Repository $assetRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );

        $this->eligibilityHelper = $eligibilityHelper;
        $this->floaPayManagement = $floaPayManagement;
        $this->assetRepository = $assetRepository;
    }

    /**
     * Check if module is enabled and display allowed
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled()
    {
        if ($this->getStore()->getCurrentCurrency()->getCode() !== 'EUR') {
            return false;
        }

        if (!$this->getConfig(FloaScopeConfigInterface::IS_ACTIVE_PATH) || !$this->getConfig(FloaScopeConfigInterface::WIDGET_PRODUCT_PATH)) {
            return false;
        }

        return true;
    }

    /**
     * Get current store
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        if (!$this->store) {
            try {
                $this->store = $this->_storeManager->getStore();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->store = $this->_storeManager->getStores()[0];
            }
        }

        return $this->store;
    }

    /**
     * Get current store code
     *
     * @return int|string|null
     */
    public function getStoreCode()
    {
        if (!$this->storeCode) {
            $this->storeCode = ($this->getStore()) ? $this->getStore()->getCode() : null;
        }

        return $this->storeCode;
    }

    /**
     * Get image src field
     *
     * @param string $file
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImageSrc(string $file)
    {
        $asset = $this->_assetRepo->createAsset('FLOA_Payment::images/' . $file);

        return $asset->getUrl();
    }

    /**
     * Get config value
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getConfig(string $path)
    {
        return $this->_scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreCode()
        );
    }

    /**
     * Get currency sign
     *
     * @return string|null
     */
    public function getCurrendySign()
    {
        return ($this->getStore()) ? $this->getStore()->getCurrentCurrency()->getCurrencySymbol() : null;
    }

    /**
     * Get language context
     *
     * @return mixed
     */
    public function getLanguageContext()
    {
        return substr($this->getConfig('general/locale/code'), 0, 2);
    }

    /**
     * Get country code
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        $country = $this->getConfig(FloaScopeConfigInterface::COUNTRY_CODE_PATH);

        return strtolower($country);
    }

    /**
     * Get CGV URL
     *
     * @return string|null
     */
    public function getCgvUrl()
    {
        return $this->getConfig(FloaScopeConfigInterface::CGV_URL);
    }

    /**
     * Get current Product URL
     *
     * @return string|null
     */
    public function getCurrentProductUrl()
    {
        return $this->getProduct()->getProductUrl();
    }

    /**
     * Get Refresh Ajax URL
     *
     * @return string|null
     */
    public function getOfferUrl()
    {
        return $this->getUrl('floa/ajax/offer');
    }

    /**
     * Get Images Path
     *
     * @return string|null
     */
    public function getImagesPath()
    {
        $logoPath = $this->assetRepository
            ->createAsset('FLOA_Payment::images/floapay-logo.svg')
            ->getUrl();

        return str_replace('floapay-logo.svg', '', $logoPath);
    }

    public function getOffers($price = false)
    {
        if ($price === false) {
            $price = $this->getProduct()->getFinalPrice();
        }
        $this->offers = [];

        $offersToSchedule = [3, 4, 1];
        foreach ($offersToSchedule as $oneOffer) {
            try {
                $this->getScheduleOffer(
                    'cb' . $oneOffer . 'x' . ($oneOffer == 1 ? 'd' : ''),
                    $oneOffer,
                    $price,
                    $this->offers
                );
            } catch (\Exception $ex) {
                $logger = new FloaPayLogger();
                $logger->info("Widget - Exception - cb{$oneOffer} - " . $ex->getMessage());
            }
        }

        return $this->offers;
    }

    protected function getScheduleOffer($method, $maturity, $price, &$offers)
    {
        $methodActive = $this->getConfig('payment/floa_payment/' . $method . '_active');
        if (empty($methodActive)) {
            return;
        }

        $this->floaPayManagement->initialize($method, ScopeInterface::SCOPE_STORE, $this->getStore()->getId());
        if (!$this->floaPayManagement->active) {
            return;
        }

        $reportDelayInDays = false;
        if ($method == 'cb1xd' && $this->getConfig(FloaScopeConfigInterface::REPORT_MODE) == 1) {
            $reportDelayInDays = 30;
        } elseif ($method == 'cb1xd' && $this->getConfig(FloaScopeConfigInterface::REPORT_MODE) == 2) {
            $reportDelayInDays = $this->getConfig(FloaScopeConfigInterface::REPORT_DELAY);
        }

        $offersAPI = $this->floaPayManagement->getEstimatedSchedule($price, $reportDelayInDays);
        if (isset($offersAPI['state']) && $offersAPI['state'] === true) {
            $offers[] = [
                'maturity' => $maturity,
                'totalamount' => str_replace('.', ',', $offersAPI['amount']),
                'amount' => str_replace('.', ',', $offersAPI['schedules'][0]->amount),
                'fees' => $offersAPI['amount'] - ($price * 100),
                'schedules' => $offersAPI['schedules'],
            ];
        }
    }

    public function getSelectedOffer()
    {
        if (empty($this->offers)) {
            return [];
        }

        return $this->offers[0];
    }
}
