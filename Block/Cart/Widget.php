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

namespace FLOA\Payment\Block\Cart;

use FLOA\Payment\Helpers\EligibilityHelper;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FloaPayLogger;
use FLOA\Payment\Model\FloaPayManagement;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Asset\Repository;

class Widget extends \FLOA\Payment\Block\Product\Widget
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

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
        CheckoutSession $checkoutSession,
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
            $eligibilityHelper,
            $floaPayManagement,
            $assetRepository,
            $data
        );

        $this->checkoutSession = $checkoutSession;
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

        if (!$this->getConfig(FloaScopeConfigInterface::IS_ACTIVE_PATH) || !$this->getConfig(FloaScopeConfigInterface::WIDGET_CART_PATH)) {
            return false;
        }

        return true;
    }

    /**
     * Get current Product URL
     *
     * @return string|null
     */
    public function getCurrentProductUrl()
    {
        return '';
    }

    public function getOffers($price = false)
    {
        $price = $this->checkoutSession->getQuote()->getGrandTotal();
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
}
