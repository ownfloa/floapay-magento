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

namespace FLOA\Payment\Controller\Eligibility;

use FLOA\Payment\Model\FloaPayConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;

class Plans extends Action implements ActionInterface
{
    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \FLOA\Payment\Model\FloaPayConfigProvider */
    protected $floaPayconfig;

    /** @var \Magento\Quote\Api\CartManagementInterface */
    protected $cartManagementInterface;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var string */
    protected $_storeScope;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var int */
    protected $_store_id;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Framework\Pricing\Helper\Data */
    protected $priceHelper;

    /** @var string[]|bool */
    protected $methodPayment;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \FLOA\Payment\Model\FloaPayConfigProvider $floaPayconfig
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        CheckoutSession $checkoutSession,
        \FLOA\Payment\Model\FloaPayConfigProvider $floaPayconfig,
        CartManagementInterface $cartManagementInterface,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $resultJsonFactory,
        PriceHelper $priceHelper
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->priceHelper = $priceHelper;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_storeScope = ScopeInterface::SCOPE_STORE;
        $this->_storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->_scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->_store_id = $this->_storeManager->getStore()->getId();
        $this->cartManagementInterface = $cartManagementInterface;
        $this->orderRepository = $orderRepository;
        $this->floaPayconfig = $floaPayconfig;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $plans = $this->floaPayConfig->getAvailablePlans();

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($plans);
    }
}
