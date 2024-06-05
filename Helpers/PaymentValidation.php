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

use FLOA\Payment\Model\FloaPayManagement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Processor as PaymentProcessor;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PaymentValidation
{
    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \FLOA\Payment\Model\FloaPayManagement */
    protected $floaPayManagement;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $quoteRepository;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Magento\Sales\Api\OrderManagementInterface */
    protected $orderManagement;

    /** @var \FLOA\Payment\Model\FormValidation\Validator */
    protected $_formValidator;

    /** @var string */
    protected $storeScope;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var int */
    protected $store_id;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Sales\Model\Order\Payment\Processor */
    protected $paymentProcessor;

    /** @var \Magento\Sales\Model\Service\InvoiceService */
    protected $invoiceService;

    /** @var \Magento\Framework\DB\Transaction */
    protected $transaction;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    protected $transactionBuilder;

    /**
     * __construct
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param FloaPayManagement $floaPayManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param OrderManagementInterface $orderManagement
     * @param CheckoutSession $checkoutSession
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param BuilderInterface $transactionBuilder
     * @param PaymentProcessor $paymentProcessor
     *
     * @return void
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        FloaPayManagement $floaPayManagement,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        OrderManagementInterface $orderManagement,
        CheckoutSession $checkoutSession,
        InvoiceService $invoiceService,
        Transaction $transaction,
        BuilderInterface $transactionBuilder,
        PaymentProcessor $paymentProcessor
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->transactionBuilder = $transactionBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->orderManagement = $orderManagement;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->floaPayManagement = $floaPayManagement;
        $this->scopeConfig = $scopeConfig;
        $this->storeScope = ScopeInterface::SCOPE_STORE;
        $this->store_id = $storeManagerInterface->getStore()->getId();
    }

    /**
     * Validate
     *
     * @param mixed $data
     *
     * @return void
     */
    public function validate($data)
    {
        $errorMessage = __('There was an error when validating your payment. Please try again or contact us if the problem persists.')->render();
        $refusedMessage = __('Sorry, your financing request was not accepted by Floa Bank.')->render()
            . ' ' . __('The financing is fully managed by Floa Bank which reserves the right of refusal according to its own rules.')->render()
            . ' ' . __('The reason for refusal cannot be be communicated to you because of banking secrecy.')->render();
        $orderRef = isset($data['orderRef']) ? $data['orderRef'] : false;
        $eligibilityToken = isset($data['scoringToken']) ? $data['scoringToken'] : false;
        $code = isset(explode('_', $orderRef)[0]) ? explode('_', $orderRef)[0] : null;
        $returnCode = isset($data['returnCode']) ? $data['returnCode'] : false;

        if ($orderRef === false || $code === null || $eligibilityToken === false) {
            return $this->_paymentValidation(false, __('Bad request'));
        }

        $quoteId = isset(explode('_', $orderRef)[1]) ? explode('_', $orderRef)[1] : null;
        try {
            $quote = $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            return $this->_paymentValidation(false, $errorMessage);
        }
        $quotePayment = $quote->getPayment();
        if (!$quotePayment) {
            return $this->_paymentValidation(false, $errorMessage);
        }
        $floaInformations = $quotePayment->getAdditionalInformation();
        try {
            $order = $this->orderRepository->get($floaInformations['FloaOrderId']);
        } catch (NoSuchEntityException $e) {
            return $this->_paymentValidation(false, $errorMessage);
        }
        if (0 == $order->getInvoiceCollection()->count() && in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT])) {
            return $this->validateOrderInvoice(
                $floaInformations,
                $code,
                $eligibilityToken,
                $quotePayment,
                $quote,
                $order,
                $returnCode,
                $refusedMessage,
                $errorMessage
            );
        } elseif ($order->getState() == Order::STATE_CANCELED) {
            return $this->_paymentValidation(false, __('Order is canceled'));
        } elseif (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_HOLDED, Order::STATE_PAYMENT_REVIEW])) {
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->orderRepository->save($order);

            return $this->_paymentValidation(true, '', 'checkout/onepage/success');
        }

        return $this->_paymentValidation(false, $errorMessage);
    }

    /**
     * ValidateOrderInvoice
     *
     * @param mixed $floaInformations
     * @param mixed $code
     * @param mixed $eligibilityToken
     * @param mixed $quotePayment
     * @param mixed $order
     * @param mixed $returnCode
     * @param mixed $refusedMessage
     * @param mixed $errorMessage
     *
     * @return void
     */
    private function validateOrderInvoice(
        $floaInformations,
        $code,
        $eligibilityToken,
        $quotePayment,
        $quote,
        $order,
        $returnCode,
        $refusedMessage,
        $errorMessage
    ) {
        if ($floaInformations['FloaSecureKey'] == $eligibilityToken) {
            $this->floaPayManagement->initialize($code, $this->storeScope, $this->store_id);
            if ($quotePayment->getMethod() == 'cb10x') {
                $result = $this->floaPayManagement->getPaymentResultFromPaymentSchedules($quotePayment->getAdditionalInformation('FloaOrderRef'));
            } else {
                $result = $this->floaPayManagement->getPaymentResult($quotePayment->getAdditionalInformation('FloaSessionId'));
            }
            $schedules = $this->floaPayManagement->getPaymentSchedules($quotePayment->getAdditionalInformation('FloaOrderRef'));
            $payment = $order->getPayment();
            $floaTools = new FloaTools();
            $totalDueEligibility = $floaTools->convertToInt($order->getTotalDue()) + $quotePayment->getAdditionalInformation('FloaFeesAmount');
            if (isset($result['state']) && $result['state'] === true) {
                if (in_array($result['datas']['paymentResultCode'], [0, 4])) {
                    if ($result['datas']['amount'] == $totalDueEligibility) {
                        $payment = $order->getPayment();
                        $payment
                            ->setLastTransId($eligibilityToken)
                            ->setTransactionId($eligibilityToken);
                        $this->transactionBuilder
                            ->setPayment($payment)
                            ->setOrder($order)
                            ->setTransactionId($eligibilityToken)
                            ->setFailSafe(true);
                        if (isset($schedules['schedules']) && $schedules['schedules'][0]->state == 'init' && !in_array($quotePayment->getMethod(), ['cb1xd', 'cb10x'])) {
                            $this->transactionBuilder->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH);
                        } else {
                            $this->transactionBuilder->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                        }
                        $payment->setParentTransactionId(null);
                        $payment->save();
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        if (isset($schedules['schedules']) && $schedules['schedules'][0]->state == 'init' && !in_array($quotePayment->getMethod(), ['cb1xd', 'cb10x'])) {
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::NOT_CAPTURE);
                        } else {
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                        }
                        $invoice->register()->save();
                        $transaction = $this->transaction->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transaction->save();
                        $order = $this->orderRepository->get($order->getEntityId());
                        $this->checkoutSession
                            ->setLastQuoteId($quote->getId())
                            ->setLastSuccessQuoteId($quote->getId());

                        $this->checkoutSession->setLastOrderId($order->getId())
                                              ->setLastRealOrderId($order->getIncrementId())
                                              ->setLastOrderStatus($order->getStatus());

                        if ($this->scopeConfig->getValue('sales_email/order/enabled', $this->storeScope) == 1) {
                            $emailSender = \Magento\Framework\App\ObjectManager::getInstance()
                                ->create(\Magento\Sales\Model\Order\Email\Sender\OrderSender::class);
                            $emailSender->send($order);
                        }
                        if (isset($schedules['schedules']) && $schedules['schedules'][0]->state == 'init' && !in_array($quotePayment->getMethod(), ['cb1xd', 'cb10x'])) {
                            $this->_addOrderComment($order, __('Payment anthorization accepted') . ' ' . __('Capture payment required'), Order::STATE_PROCESSING);
                        } else {
                            $this->_addOrderComment($order, __('Payment captured'), Order::STATE_PROCESSING);
                        }
                        $this->orderRepository->save($order);

                        return $this->_paymentValidation(true, '', 'checkout/onepage/success');
                    } else {
                        return $this->_paymentValidation(false, $errorMessage);
                    }
                } elseif (in_array($result['datas']['paymentResultCode'], [1, 2])) {
                    $this->_addOrderComment($order, __('Order payment refused'), Order::STATE_CANCELED);
                    $this->orderManagement->cancel($order->getId());

                    return $this->_paymentValidation(false, $refusedMessage);
                } else {
                    $quote->setIsActive(true);
                    $this->checkoutSession->restoreQuote();
                    $this->quoteRepository->save($quote);
                    $this->checkoutSession->setLastQuoteId($quote->getId())
                        ->setLastSuccessQuoteId($quote->getId())
                        ->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId());
                    $this->_addOrderComment($order, __('Technical error on Floa Bank payment validation'), Order::STATE_CANCELED);
                    $this->orderManagement->cancel($order->getId());

                    return $this->_paymentValidation(false, $errorMessage);
                }
            } else {
                if (in_array($returnCode, [1, 2]) && $quotePayment->getMethod() == 'cb10x') {
                    $this->_addOrderComment($order, __('Order payment refused'), Order::STATE_CANCELED);
                    $this->orderManagement->cancel($order->getId());

                    return $this->_paymentValidation(false, $refusedMessage);
                } else {
                    return $this->_paymentValidation(false, $errorMessage);
                }
            }
        } else {
            return $this->_paymentValidation(false, $errorMessage);
        }
    }

    /**
     * PaymentValidation
     *
     * @param mixed $state
     * @param mixed $message
     * @param mixed $returnPath
     *
     * @return void
     */
    private function _paymentValidation($state = false, $message = '', $returnPath = 'checkout/cart')
    {
        return [
            'state' => $state,
            'message' => $message,
            'returnPath' => $returnPath,
        ];
    }

    /**
     * AddOrderComment
     *
     * @param mixed $order
     * @param mixed $comment
     * @param mixed $state
     *
     * @return void
     */
    private function _addOrderComment($order, $comment, $state)
    {
        if (method_exists($order, 'addCommentToStatusHistory') && is_callable([$order, 'addCommentToStatusHistory'])) {
            $order->addCommentToStatusHistory($comment, $state);
        } else {
            $order->addStatusHistoryComment($comment, $state);
        }
    }
}
