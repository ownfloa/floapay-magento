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
use Magento\Framework\View\Result\PageFactory;

class Ipnold extends \Magento\Framework\App\Action\Action
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
     * Construct IPN OLD
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
     * Execute IPN Old
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $return = $this->paymentValidation->validate($post);
        if ($return['state'] != true) {
            header('HTTP/1.1 500 Internal Server Error');
            return 'Request executed with errors';
        }
        return 'Request executed';
    }
}