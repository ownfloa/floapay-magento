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

namespace FLOA\Payment\Block\Adminhtml\Sales;

use Magento\Backend\Model\Url;
use Magento\Sales\Model\Order;
use Magento\Framework\Registry;
use FLOA\Payment\Helpers\FloaTools;
use Magento\Backend\Block\Template;
use Magento\Framework\UrlInterface;
use Magento\Directory\Model\Currency;
use Magento\Store\Model\ScopeInterface;
use FLOA\Payment\Model\FloaPayManagement;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class FloaPayment extends Template
{
    /** @var Registry */
    protected $coreRegistry;

    /** @var Currency */
    protected $currency;
    
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var FloaPayManagement */
    protected $floaPayManagement;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var string */
    protected $storeScope;

    /** @var PriceHelper */
    protected $priceHelper;

    /** @var HttpRequest */
    protected $request;

    /** @var HttpResponse */
    protected $response;

    /** @var RedirectInterface */
    protected $redirect;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var Url */
    protected $backendUrlManager;

    /** @var ManagerInterface */
    protected $messageManager;

    /** @var TransactionRepositoryInterface */
    protected $transactionRepository;

    /** @var int */
    protected $store_id;

    /** @var mixed */
    protected $orderPayment;

    /** @var string */
    protected $method;

    /** @var float */
    protected $totalAmount;

    /** @var mixed */
    protected $partialCancels;

    /** @var mixed */
    protected $refundedAmount;

    /** @var mixed */
    protected $toBeRefunded;

    /** @var \Magento\Framework\Serialize\SerializerInterface */
    protected $serializer;
        
    /**
     * Construct
     *
     * @param Context $context
     * @param Registry $registry
     * @param Currency $currency
     * @param UrlInterface $urlBuilder
     * @param FloaPayManagement $floaPayManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param PriceHelper $priceHelper
     * @param Url $backendUrlManager
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param RedirectInterface $redirect
     * @param ManagerInterface $messageManager
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param SerializerInterface $serializer
     * @param array $data
     *
     * @return void
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Currency $currency,
        UrlInterface $urlBuilder,
        FloaPayManagement $floaPayManagement,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        PriceHelper $priceHelper,
        Url $backendUrlManager,
        HttpRequest $request,
        HttpResponse $response,
        RedirectInterface $redirect,
        ManagerInterface $messageManager,
        TransactionRepositoryInterface $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->coreRegistry          = $registry;
        $this->currency              = $currency;
        $this->priceHelper           = $priceHelper;
        $this->urlBuilder            = $urlBuilder;
        $this->request               = $request;
        $this->response              = $response;
        $this->redirect              = $redirect;
        $this->orderRepository       = $orderRepository;
        $this->backendUrlManager     = $backendUrlManager;
        $this->messageManager        = $messageManager;
        $this->transactionRepository = $transactionRepository;
        $this->scopeConfig           = $scopeConfig;
        $this->storeScope            = ScopeInterface::SCOPE_STORE;
        $this->store_id              = $storeManagerInterface->getStore()->getId();
        $this->floaPayManagement     = $floaPayManagement;
        $this->serializer            = $serializer;
        $this->orderPayment          = $this->getOrder()->getPayment();
        $this->method                = $this->orderPayment->getMethod();
        $this->totalAmount           = isset($this->orderPayment->getAdditionalInformation()['FloaTotalAmount'])
            ? (int) ($this->orderPayment->getAdditionalInformation()['FloaTotalAmount']-$this->orderPayment->getAdditionalInformation()['FloaFeesAmount']) : 0;
        $this->partialCancels        = isset($this->orderPayment->getAdditionalInformation()['FloaPartialsCancels'])
            ? $this->serializer->unserialize($this->orderPayment->getAdditionalInformation()['FloaPartialsCancels']) : [];
        $this->refundedAmount        = $this->getPartialsCancelsAmount();
        $this->toBeRefunded          = $this->totalAmount - $this->refundedAmount;
        if ($this->isFloaOrder() === false) {
            parent::__construct($context, $data);
        }

        $this->floaPayManagement->initialize(
            $this->method,
            $this->storeScope,
            $this->store_id,
            $this->orderPayment->getAdditionalInformation('FloaMerchantId'),
            $this->orderPayment->getAdditionalInformation('FloaMerchantSiteId')
        );
        
        if ($this->request->getParam('floaAction')) {
            if ($this->request->getParam('floaAction') == "refund") {
                $floaTools = new FloaTools();
                $amountToRefund = (int) $floaTools->convertToInt(str_replace(',', '.', $this->request->getParam('amountToRefund')));
                $oldAmount      = (int) $this->request->getParam('oldAmount');
                $newAmount      = $oldAmount - $amountToRefund;
                if ($oldAmount == $this->getOldAmount() && $amountToRefund > 0 && $amountToRefund <= $this->toBeRefunded) {
                    $apiRefund = $this->floaPayManagement->updateOrder(
                        $this->orderPayment->getAdditionalInformation('FloaOrderRef'),
                        $this->orderPayment->getAdditionalInformation('FloaSecureKey'),
                        $this->toBeRefunded,
                        $newAmount,
                        $this->orderPayment->getAdditionalInformation('FloaMerchantId'),
                        $this->orderPayment->getAdditionalInformation('FloaMerchantSiteId')
                    );
                    if ($apiRefund['state'] == true) {
                        $cancels = $this->updatePartialCancels($amountToRefund);
                        $this->orderPayment->setAdditionalInformation('FloaPartialsCancels', $cancels);
                        $this->orderPayment->save();
                        $this->partialCancels     = isset($this->orderPayment->getAdditionalInformation()['FloaPartialsCancels'])
                            ? $this->serializer->unserialize($this->orderPayment->getAdditionalInformation()['FloaPartialsCancels']) : [];
                        $this->refundedAmount     = $this->getPartialsCancelsAmount();
                        $this->toBeRefunded       = $this->totalAmount-$this->refundedAmount;
                        $this->messageManager->addSuccess(__('Refund successfully completed.'));
                    } else {
                        $this->messageManager->addError($apiRefund['message']);
                    }
                } else {
                    $this->messageManager->addError(__('The amount to be refunded is invalid.'));
                }
            } elseif ($this->request->getParam('floaAction') == "pay") {
                if ($this->isPayableOrder()) {
                    $action = $this->floaPayManagement->payOrderRank(
                        $this->orderPayment->getAdditionalInformation('FloaOrderRef'),
                        $this->orderPayment->getAdditionalInformation('FloaMerchantId'),
                        $this->orderPayment->getAdditionalInformation('FloaMerchantSiteId')
                    );
                    if (isset($action['state']) && $action['state'] == true) {
                        $order = $this->getOrder();
                        foreach ($order->getInvoiceCollection() as $invoice) {
                            $invoiceOrder = $invoice;
                        }
                        $invoiceOrder->capture($invoiceOrder);
                        $invoiceOrder->save();
                        $order->save();
                        if (method_exists($order, 'addCommentToStatusHistory') && is_callable([$order, 'addCommentToStatusHistory'])) {
                            $order->addCommentToStatusHistory(__('Payment captured'), Order::STATE_PROCESSING);
                        } else {
                            $order->addStatusHistoryComment(__('Payment captured'), Order::STATE_PROCESSING);
                        }
                        $this->orderRepository->save($order);
                        $this->messageManager->addSuccess(__('Order successfully captured.'));
                    } else {
                        $this->messageManager->addError($action['message']);
                    }
                }
            }
            $this->orderPayment = $this->getOrder()->getPayment();
            $this->floaPayManagement->initialize($this->method, $this->storeScope, $this->store_id, $this->orderPayment->getAdditionalInformation('FloaMerchantId'), $this->orderPayment->getAdditionalInformation('FloaMerchantSiteId'));
        }
        parent::__construct($context, $data);
    }
        
    /**
     * Get Order
     *
     * @return mixed
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }
    
    /**
     * Get OrderId
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }
    
    /**
     * Is Floa Order
     *
     * @return bool
     */
    public function isFloaOrder()
    {
        $order   = $this->getOrder();
        $payment = $order->getPayment();
        return (isset($this->getSchedules()['schedules'])
            && isset($payment->getAdditionalInformation()['FloaOrderRef'])) ? true : false;
    }
    
    /**
     * Get Schedules
     *
     * @return mixed
     */
    public function getSchedules()
    {
        return $this->floaPayManagement->getPaymentSchedules($this->orderPayment->getAdditionalInformation('FloaOrderRef'), $this->orderPayment->getAdditionalInformation('FloaMerchantId'), $this->orderPayment->getAdditionalInformation('FloaMerchantSiteId'));
    }
    
    /**
     * Is Payable Order
     *
     * @return bool
     */
    public function isPayableOrder()
    {
        if (isset($this->getSchedules()['schedules'])
            && $this->getSchedules()['schedules'][0]->state == "init"
            && in_array($this->method, ['cb3x', 'cb4x'])
        ) {
            return true;
        }
        return false;
    }
    
    /**
     * GetFloaOrderRef
     *
     * @return mixed
     */
    public function getFloaOrderRef()
    {
        return $this->orderPayment->getAdditionalInformation('FloaOrderRef');
    }
    
    /**
     * GetFloaPaymentMethod
     *
     * @return mixed
     */
    public function getFloaPaymentMethod()
    {
        return $this->method;
    }
    
    /**
     * GetFloaOrderCancelable
     *
     * @return bool
     */
    public function getFloaOrderCancelable()
    {
        // updateorder possible quand ? @TODO
        if ($this->getOldAmount() <= 0 || !isset($this->getSchedules()['schedules'])) {
            return false;
        }
        return true;
    }
    
    /**
     * GetFloaFeesAmount
     *
     * @return mixed
     */
    public function getFloaFeesAmount()
    {
        return $this->orderPayment->getAdditionalInformation('FloaFeesAmount');
    }
    
    /**
     * GetFloaTotalAmount
     *
     * @return mixed
     */
    public function getFloaTotalAmount()
    {
        return $this->orderPayment->getAdditionalInformation('FloaTotalAmount');
    }
    
    /**
     * GetOldAmount
     *
     * @return mixed
     */
    public function getOldAmount()
    {
        return $this->toBeRefunded;
    }
    
    /**
     * ToDecimalPrice
     *
     * @param  mixed $price
     * @return mixed
     */
    public function toDecimalPrice($price)
    {
        $floaTools = new FloaTools();
        return $this->priceHelper->currency($floaTools->convertToFloat($price), true, false);
    }
    
    /**
     * GetBackendOrderUrl
     *
     * @param  mixed $action
     * @return mixed
     */
    public function getBackendOrderUrl($action = false)
    {
        $datas['order_id'] = $this->getOrder()->getId();
        if ($action) {
            $datas['floaAction'] = $action;
        }
        return $this->backendUrlManager->getUrl('sales/order/view', $datas);
    }
    
    /**
     * UpdatePartialCancels
     *
     * @param  mixed $amount
     * @return mixed
     */
    public function updatePartialCancels($amount)
    {
        $partialCancels = $this->partialCancels;
        $partialCancels[] = [
            'date' => date('d/m/Y H:i:s'),
            'amount' => (int) $amount
        ];
        return $this->serializer->serialize($partialCancels);
    }

    /**
     * GetPartialsCancels
     *
     * @return mixed
     */
    public function getPartialsCancels()
    {
        return $this->partialCancels;
    }
    
    /**
     * GetPartialsCancelsAmount
     *
     * @return mixed
     */
    public function getPartialsCancelsAmount()
    {
        $refundAmount = 0;
        foreach ($this->partialCancels as $refund) {
            $refundAmount += $refund['amount'];
        }
        return $refundAmount;
    }
}
