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

namespace FLOA\Payment\Controller\Back;

use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Index extends Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Quote\Model\QuoteRepository */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->quoteRepository   = $quoteRepository;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(true);
        $this->checkoutSession->restoreQuote();
        $this->quoteRepository->save($quote);
        $this->checkoutSession->setLastQuoteId($quote->getId())->setLastSuccessQuoteId($quote->getId())->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());
        return $this->_redirect('checkout/cart');
    }
}
