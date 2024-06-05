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

use FLOA\Payment\Helpers\FloaTools;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FloaPayLogger;
use FLOA\Payment\Model\FloaPayManagement;
use FLOA\Payment\Model\FormValidation\Validator;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class Index extends Action implements ActionInterface
{
    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \FLOA\Payment\Model\FloaPayManagement */
    protected $floaPayManagement;

    /** @var \Magento\Quote\Api\CartManagementInterface */
    protected $cartManagementInterface;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \FLOA\Payment\Model\FormValidation\Validator */
    protected $_formValidator;

    /** @var string */
    protected $_storeScope;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var int */
    protected $_store_id;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Zend_Log */
    protected $logger;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \FLOA\Payment\Model\FloaPayManagement $floaPayManagement
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \FLOA\Payment\Model\FormValidation\Validator $formValidator
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        CheckoutSession $checkoutSession,
        FloaPayManagement $floaPayManagement,
        CartManagementInterface $cartManagementInterface,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Validator $formValidator
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->_storeScope = ScopeInterface::SCOPE_STORE;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_store_id = $this->_storeManager->getStore()->getId();
        $this->floaPayManagement = $floaPayManagement;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->orderRepository = $orderRepository;
        $this->_formValidator = $formValidator;
        $this->logger = new FloaPayLogger();
    }

    /**
     * Execute
     */
    public function execute()
    {
        $rawValues = $this->request->getPost();
        $eligibilityDatas = isset($rawValues['payment']) ? $rawValues['payment'] : null;
        $code = isset($eligibilityDatas['methodFloa']) ? $eligibilityDatas['methodFloa'] : '';

        $countryCode = $this->_scopeConfig->getValue(
            FloaScopeConfigInterface::COUNTRY_CODE_PATH,
            $this->_storeScope,
            $this->_store_id
        );
        $quote = $this->checkoutSession->getQuote();
        $customer = $quote->getCustomer();
        $shippingAddress = $quote->getShippingAddress();
        $billingAddress = $quote->getBillingAddress();

        $civility = $eligibilityDatas['civility'] ?? '';
        $maiden_name = $eligibilityDatas['maiden_name'] ?? '';
        $secondLastname = $eligibilityDatas['second_last_name'] ?? '';
        $birth_date = $eligibilityDatas['birth_date'] ?? '';
        $birth_zip_code = $eligibilityDatas['birth_zip'] ?? '';
        $phone_number = $eligibilityDatas['phone_number'] ?? '';
        $deferred_time = $eligibilityDatas['deferred_time'] ?? '';
        $nationalityId = $eligibilityDatas['nationality_id'] ?? '';

        $customerGenderId = $customer->getGender();
        if (true === empty($civility) && false === empty($customerGenderId)) {
            switch ($customerGenderId) {
                case 1:
                    $civility = 'Mr';
                    break;
                case 2:
                    $civility = 'Mrs';
                    break;
                default:
                    $civility = '';
            }
        }

        $customerTelephone = str_replace(' ', '', $billingAddress->getTelephone() ?? '');
        if (true === empty($phone_number) && false === empty($customerTelephone)) {
            $phone_number = $customerTelephone;
        }

        $customerTaxVat = $customer->getTaxvat();
        if (true === empty($nationalityId) && false === empty($customerTaxVat)) {
            $nationalityId = $customer->getTaxvat();
        }

        if ($maiden_name) {
            $maiden_name = str_replace("'", '', str_replace('-', '', $maiden_name));
        }

        $reportDelayInDays = 0;
        if ($code == 'cb1xd' && $this->_scopeConfig->getValue(FloaScopeConfigInterface::REPORT_MODE, $this->_storeScope, $this->_store_id) == 1 && $countryCode === 'FR') {
            $reportDelayInDays = (strtotime($deferred_time) - strtotime(date('Y-m-d'))) / 86400;
        } elseif ($code == 'cb1xd') {
            $reportDelayInDays = (int) $this->_scopeConfig->getValue(FloaScopeConfigInterface::REPORT_DELAY, $this->_storeScope, $this->_store_id) ?: 30;
            $deferred_time = date('Y-m-d', strtotime('+' . $reportDelayInDays . 'days'));
        }

        if ($reportDelayInDays < 0 || $reportDelayInDays > 30) {
            $reportDelayInDays = 30;
            $deferred_time = date('Y-m-d', strtotime('+' . $reportDelayInDays . 'days'));
        }

        $regexMaidenName = Validator::LASTNAME_REGEX;
        $regexSecondLastName = Validator::LASTNAME_REGEX;
        $regexPhone = Validator::PHONE_REGEX['default'];
        switch ($countryCode) {
            case 'FR':
            default:
                $maiden_name = trim($maiden_name);
                $regexCp = Validator::CP_REGEX[$countryCode];
                $isScoreCpValid = preg_match($regexCp, $birth_zip_code);
                $isSecondLastNameValid = true;
                $isScoreMaidenNameValid = ($civility == 2) ? preg_match($regexMaidenName, $maiden_name) : true;
                $isNifValid = true;
                break;
            case 'BE':
                if (empty(trim($birth_zip_code))) {
                    $isScoreCpValid = true;
                    $birth_zip_code = Validator::INTERNATIONAL_ZIP;
                } else {
                    $regexCp = Validator::CP_REGEX[$countryCode];
                    $isScoreCpValid = preg_match($regexCp, $birth_zip_code);
                }
                $isSecondLastNameValid = true;
                $isScoreMaidenNameValid = ($civility == 2) ? preg_match($regexMaidenName, $maiden_name) : true;
                $isNifValid = true;
                break;
            case 'IT':
            case 'PT':
                $isScoreCpValid = true;
                $birth_zip_code = Validator::INTERNATIONAL_ZIP;
                $isSecondLastNameValid = true;
                $isScoreMaidenNameValid = true;
                $regexNif = Validator::NIF_REGEX[$countryCode];
                $isNifValid = preg_match($regexNif, $nationalityId);
                break;
            case 'ES':
                $secondLastname = trim($secondLastname);
                $isScoreCpValid = true;
                $birth_zip_code = Validator::INTERNATIONAL_ZIP;
                $isScoreMaidenNameValid = true;
                $isSecondLastNameValid = $secondLastname != '' ? preg_match($regexSecondLastName, $secondLastname) : true;
                $regexNif = Validator::NIF_REGEX[$countryCode];
                $nationalityId = strtoupper($nationalityId);
                $isNifValid = preg_match($regexNif, $nationalityId);
                if ($isNifValid) {
                    $isNifValid = $this->isEsNifValid($nationalityId, $birth_zip_code, $billingAddress->getPostcode());
                }
                break;
        }
        $isScoreCivilityValid = in_array($civility, ['Mr', 'Mrs']);
        $isScorePhoneValid = preg_match($regexPhone, $phone_number);
        $isScoreDdnValid = preg_match(Validator::REGEX_DATE, $birth_date);
        $scoreDdnObject = new \DateTime($birth_date);
        $isMajorCustomer = $scoreDdnObject->diff(new \DateTime())->y >= 18;
        $isPaymentDateValid = true;
        if (($code == 'cb1xd' && $reportDelayInDays !== 0 && $countryCode === 'FR')
            && (
                !preg_match(Validator::REGEX_DATE, $deferred_time)
                || $deferred_time > date('Y-m-d', strtotime('+30days'))
                || $deferred_time <= date('Y-m-d')
            )) {
            $isPaymentDateValid = false;
        }
        $errors = [];
        if (!$isScoreCivilityValid) {
            $errors[] = __('Civility is invalid.', 'floapay');
        }
        if (!$isSecondLastNameValid) {
            $errors[] = __('Second lastname is invalid.', 'floapay');
        }
        if (!$isScoreDdnValid) {
            $errors[] = __('Birth date is invalid.', 'floapay');
        }
        if (!$isScoreCpValid) {
            $errors[] = __('Postal code is invalid.', 'floapay');
        }
        if (!$isNifValid) {
            $errors[] = __('NIF is invalid.', 'floapay');
        }
        if (!$isScorePhoneValid) {
            $errors[] = __('Phone number is invalid.', 'floapay');
        }
        if (!$isMajorCustomer) {
            $errors[] = __('You must be over 18 years old.', 'floapay');
        }
        if (!$isScoreMaidenNameValid) {
            $errors[] = __('Maiden name is invalid.', 'floapay');
        }
        if (!$isPaymentDateValid) {
            $errors[] = __('Payment date is invalid.', 'floapay');
        }

        if (false === empty($errors)) {
            return $this->_returnState(false, $errors);
        }

        $eligibilityDatas = [
            'score_civility' => $civility,
            'score_ddn' => $birth_date,
            'score_cp' => $birth_zip_code,
            'method' => $code,
            'score_maidenname' => $maiden_name,
            'score_secondlastname' => $secondLastname,
            'score_phone' => $phone_number,
            'payment_date' => $deferred_time,
            'customer_email' => $billingAddress->getEmail(),
            'customer_id' => $customer->getId() ? $customer->getId() : uniqid(),
            'nationalId' => $nationalityId,
        ];

        $this->_formValidator->setBillingAddress($billingAddress);
        $this->_formValidator->validate($eligibilityDatas, $code);
        $errors = $this->_formValidator->getErrors();
        if (!empty($errors)) {
            return $this->_returnState(false, $errors);
        }
        $this->floaPayManagement->initialize($code, $this->_storeScope, $this->_store_id);
        $eligibility = $this->floaPayManagement->getEligibility($quote, $shippingAddress, $billingAddress, $eligibilityDatas, $reportDelayInDays);

        if (!isset($eligibility) || $eligibility['state'] !== true) {
            $errors[] = __('An error has occurred, please try again in a few moments...')->render();
            $this->logger->debug('Eligibility - Error state financing ');
            $this->logger->debug('[FloaPay] response' . json_encode($eligibility));
            $this->logger->debug('EligibilityDatas' . json_encode($eligibilityDatas));

            return $this->_returnState(false, $errors);
        } elseif (isset($eligibility) && $eligibility['state'] == true && !$eligibility['datas']['hasAgreement']) {
            $errors[] = __('Sorry, your financing request was not accepted by Floa Bank.')->render();
            $errors[] = __('The financing is fully managed by Floa Bank which reserves the right of refusal according to its own rules.')->render();
            $errors[] = __('The reason for refusal cannot be be communicated to you because of banking secrecy.')->render();
            $this->logger->debug(
                'Eligibility - Error financing not have agreement - response: ' . json_encode($eligibility)
            );

            return $this->_returnState(false, $errors);
        } else {
            $url = false;
            $floaTools = new FloaTools();
            $quotePayment = $quote->getPayment();
            $informations = $quotePayment->getAdditionalInformation();
            $informations['FloaFeesAmount'] = $eligibility['datas']['totalAmount'] - $floaTools->convertToInt($quote->getGrandTotal());
            $informations['FloaTotalAmount'] = $eligibility['datas']['totalAmount'];
            $informations['FloaMerchantId'] = $this->floaPayManagement->merchantId;
            $informations['FloaMerchantSiteId'] = $this->floaPayManagement->merchantSiteId;
            $informations['FloaSecureKey'] = $eligibility['datas']['token'];
            $quotePayment->setAdditionalInformation($informations)->save();
            if ($code == 'cb10x') {
                if (isset($eligibility['datas']['links'])) {
                    foreach ($eligibility['datas']['links'] as $link) {
                        if ($link->rel == 'continue-long-term-consumer-credit-process') {
                            $url = $link->href;
                        }
                    }
                }
            } else {
                $paymentSession = $this->floaPayManagement->createPaymentSession(
                    $quote,
                    $shippingAddress,
                    $billingAddress,
                    $eligibility['datas'],
                    $reportDelayInDays
                );
                if (!$paymentSession['state'] || !$paymentSession['session']->paymentSessionUrl) {
                    $errors[] = __('An error has occurred, please try again in a few moments...')->render();
                    $this->logger->debug(
                        'Eligibility - Error paymentsessionUrl - response' . json_encode($paymentSession)
                    );

                    $quotePayment->setAdditionalInformation($informations)->save();

                    return $this->_returnState(false, $errors);
                }
                $url = $paymentSession['session']->paymentSessionUrl;
                $informations['FloaSessionId'] = $paymentSession['session']->paymentSessionId;
            }
            if (!$url) {
                $errors[] = __('An error has occurred, please try again in a few moments...')->render();
                $this->logger->debug(
                    'Eligibility - Error no URL defined - response' . json_encode($eligibility)
                );

                $quotePayment->setAdditionalInformation($informations)->save();

                return $this->_returnState(false, $errors);
            }
            $informations['FloaOrderRef'] = $eligibility['datas']['orderRef'];
            $informations['FloaPaymentUrl'] = $url;
            $quotePayment->setAdditionalInformation($informations)->save();
            $quote->setPaymentMethod($code);
            $quote->getPayment()->importData(['method' => $code]);
            $quote->setInventoryProcessed(false);
            $quote->collectTotals();
            if ($quote->getCustomerEmail() === null && $quote->getBillingAddress()->getEmail() !== null) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            }
            $customerSession = ObjectManager::getInstance()->get(\Magento\Customer\Model\Session::class);
            if (!$customerSession->isLoggedIn()) {
                $quote->setCustomerId(null)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                ;
            }
            $quote->setStoreId($this->_store_id);
            $quote->save();
            $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            $order = $this->orderRepository->get($orderId);
            if (method_exists($order, 'addCommentToStatusHistory') && is_callable([$order, 'addCommentToStatusHistory'])) {
                $order->addCommentToStatusHistory(
                    __('Successfully created Floa Bank Payment. Redirecting customer & awaiting payment return.')->render(),
                    Order::STATE_PENDING_PAYMENT
                );
            } else {
                $order->addStatusHistoryComment(
                    __('Successfully created Floa Bank Payment. Redirecting customer & awaiting payment return.')->render(),
                    Order::STATE_PENDING_PAYMENT
                );
            }
            $informations['FloaOrderId'] = $orderId;
            $quotePayment->setAdditionalInformation($informations)->save();
            $order->save();
            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setData([
                'success' => true,
                'params' => [
                    'payment_url' => $url,
                ],
            ]);
        }
    }

    private function isEsNifValid($identificationNumber, $birthZip, $billingZip)
    {
        if (preg_match('/^K[0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            return false;
        } elseif (preg_match('/^L[0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            if ($billingZip != Validator::INTERNATIONAL_ZIP || $birthZip == Validator::INTERNATIONAL_ZIP) {
                return false;
            }
        } elseif (preg_match('/^[0-9]{8}[A-Z]{1}$|^[ILMOTXYZ][0-9]{7}[A-Z]{1}$/', $identificationNumber)) {
            if ($billingZip == Validator::INTERNATIONAL_ZIP) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * _returnState
     *
     * @param mixed $success
     * @param mixed $messages
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function _returnState($success = false, $messages = [])
    {
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'success' => $success,
            'messages' => $messages,
        ]);
    }
}
