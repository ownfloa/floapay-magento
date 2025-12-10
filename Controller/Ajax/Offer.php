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

namespace FLOA\Payment\Controller\Ajax;

use FLOA\Payment\Block\Product\Widget;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;

class Offer extends Action
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Widget
     */
    protected $widgetHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Maturity constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Widget $widgetHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context                                      $context,
        JsonFactory                                  $jsonResultFactory,
        LayoutFactory                                $layoutFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        ProductRepositoryInterface                   $productRepository,
        Widget                                       $widgetHelper,
        CheckoutSession                              $checkoutSession
    ) {
        $this->layoutFactory = $layoutFactory;
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->productRepository = $productRepository;
        $this->widgetHelper = $widgetHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute method
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $this->getRequest();

        $price = $request->getParam('price');
        $type = $request->getParam('type');

        if ($type === 'cart') {
            $price = $this->checkoutSession->getQuote()->getGrandTotal();
        }

        if (!$price || (bool) $request->getParam('ajax') === false) {
            return $result->setData([
                'success' => false,
                'messages' => [],
            ]);
        }

        $offers = $this->widgetHelper->getOffers($price);
        $selectedOffer = $offers[0] ?? null;

        $result->setData([
            'success' => true,
            'messages' => [
                'floapay' => [
                    'offers' => $offers,
                    'selectedOffer' => $selectedOffer,
                ],
            ],
        ]);
        return $result;
    }
}
