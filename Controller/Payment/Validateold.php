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

namespace FLOA\Payment\Controller\Payment;

use FLOA\Payment\Helpers\PaymentValidation;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Validateold extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_pageFactory;

    /** @var \FLOA\Payment\Helpers\PaymentValidation */
    protected $paymentValidation;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $_quoteRepository;

    /** @var \Magento\Payment\Model\Method\Logger */
    protected $logger;

    /** @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;

    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $_formKey;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \FLOA\Payment\Helpers\PaymentValidation $paymentValidation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        PaymentValidation $paymentValidation,
        StoreManagerInterface $storeManagerInterface,
        Logger $logger,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->_pageFactory = $pageFactory;
        $this->paymentValidation = $paymentValidation;
        $this->_storeManager = $storeManagerInterface;
        $this->logger = $logger;
        $this->_quoteRepository = $quoteRepository;

        return parent::__construct($context);
    }

    /**
     * Set Current Store
     *
     * @param mixed $post
     */
    private function setCurrentStore($post)
    {
        $orderRef = isset($post['orderRef']) ? $post['orderRef'] : false;
        $quoteId = isset(explode('_', $orderRef)[1]) ? explode('_', $orderRef)[1] : null;
        try {
            $quote = $this->_quoteRepository->get($quoteId);
            $this->_storeManager->setCurrentStore($quote->getStoreId());
        } catch (NoSuchEntityException $e) {
            $this->logger->debug([
                'where' => 'Validate exception',
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Execute
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $this->setCurrentStore($post);
        $return = $this->paymentValidation->validate($post);
        if ($return['state'] == true) {
            $redirectPath = $return['returnPath'];
        } else {
            $redirectPath = $return['returnPath'];
            if (method_exists($this->messageManager, 'addErrorMessage') && is_callable([$this->messageManager, 'addErrorMessage'])) {
                $this->messageManager->addErrorMessage($return['message']);
            } else {
                $this->messageManager->addError($return['message']);
            }
        }

        return $this->_redirect($redirectPath);
    }
}
