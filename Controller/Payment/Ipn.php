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
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;

class Ipn extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_pageFactory;

    /** @var \FLOA\Payment\Helpers\PaymentValidation */
    protected $paymentValidation;

    /** @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;

    /** @var \Magento\Framework\Data\Form\FormKey */
    protected $_formKey;

    /**
     * Construct IPN
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $pageFactory
     * @param PaymentValidation $paymentValidation
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        PaymentValidation $paymentValidation
    ) {
        $this->_pageFactory = $pageFactory;
        $this->paymentValidation = $paymentValidation;

        return parent::__construct($context);
    }

    /**
     * Execute IPN
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $return = $this->paymentValidation->validate($post);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $jsonResultFactory = $objectManager->get(\Magento\Framework\Controller\Result\JsonFactory::class);
        $result = $jsonResultFactory->create();

        $responseCode = 200;
        if ($return['state'] != true) {
            $responseCode = 500;
        }
        $result->setData(['response_code' => $responseCode, 'message' => $return['message']]);
        return $result;
    }

    /**
     * Create Csrf Validation Exception
     *
     * @param RequestInterface $request
     *
     * @return null
     *
     * @throws InvalidRequestException
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Validate Csrf
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
