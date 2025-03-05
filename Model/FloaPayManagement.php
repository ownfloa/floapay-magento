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

namespace FLOA\Payment\Model;

use CurlHandle;
use FLOA\Payment\Helpers\FloaTools;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;

class FloaPayManagement implements \FLOA\Payment\Api\FloaPayManagementInterface
{
    public const PAYMENT_GATEWAY_INTE = 'https://paymentgateway.integration-cb4x.fr/';
    public const PAYMENT_GATEWAY_PROD = 'https://paymentgateway.cb4x.fr/';

    public const ELIGIBILITY_INTE = 'https://eligibility.integration-cb4x.fr/';
    public const ELIGIBILITY_PROD = 'https://eligibility.cb4x.fr/';

    public const SERVICES_INTE = 'https://paymentservices.integration-cb4x.fr/MerchantGatewayFrontService.svc/';
    public const SERVICES_PROD = 'https://paymentservices.cb4x.fr/MerchantGatewayFrontService.svc/';

    /** @var string */
    private $method;

    /** @var string */
    private $merchantLogin;

    /** @var string */
    private $merchantPassword;

    /** @var string */
    private $env;

    /** @var Date */
    private $tokenUpdateDate;

    /** @var string */
    private $tokenEnv;

    /** @var string */
    private $authorization;

    /** @var string */
    private $contextPaymentUri;

    /** @var string */
    private $contextEligibilityUri;

    /** @var int */
    private $defaultPaymentOptionRef;

    /** @var object */
    private $paymentOption;

    /** @var int */
    private $timeoutMs;

    /** @var int */
    public $minAmount;

    /** @var int */
    public $maxAmount;

    /** @var int */
    public $merchantId;

    /** @var string */
    public $token;

    /** @var int */
    public $active;

    /** @var int */
    public $merchantSiteId;

    /** @var array */
    public $contextCheck;

    /** @var \FLOA\Payment\Helpers\PaymentHelper */
    private $paymentHelper;

    /** @var \FLOA\Payment\Helpers\EligibilityHelper */
    private $eligibilityHelper;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $_scopeConfig;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $_configWriter;

    /** @var \Magento\Framework\App\Cache\Manager */
    private $_cacheManager;

    /** @var string */
    private $contextServicesUri;

    /** @var \Magento\Framework\App\CacheInterface */
    private $cache;

    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializer;

    /** @var bool */
    private $debug;

    /** @var bool */
    private $cacheEnabled;

    /** @var DeploymentConfig */
    private $deploymentConfig;

    /** @var \Zend_Log|null */
    private $logger;

    /**
     * Construct
     *
     * @param \FLOA\Payment\Helpers\PaymentHelper $paymentHelper
     * @param \FLOA\Payment\Helpers\EligibilityHelper $eligibilityHelper
     *
     * @return void
     */
    public function __construct(
        \FLOA\Payment\Helpers\PaymentHelper $paymentHelper,
        \FLOA\Payment\Helpers\EligibilityHelper $eligibilityHelper,
        CacheInterface $cache,
        SerializerInterface $serializer,
        DeploymentConfig $deploymentConfig
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->eligibilityHelper = $eligibilityHelper;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Initialize
     *
     * @param mixed $method
     * @param mixed $storeScope
     * @param mixed $storeId
     * @param mixed $merchantId
     * @param mixed $merchantSiteId
     *
     * @return void
     */
    public function initialize($method, $storeScope, $storeId, $merchantId = null, $merchantSiteId = null)
    {
        $this->method = $method;
        $this->_scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->_configWriter = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
        $this->_cacheManager = ObjectManager::getInstance()->get(\Magento\Framework\App\Cache\Manager::class);
        $this->merchantLogin = $this->_scopeConfig->getValue(FloaScopeConfigInterface::MERCHANT_LOGIN, $storeScope, $storeId);
        $this->merchantPassword = $this->_scopeConfig->getValue(FloaScopeConfigInterface::MERCHANT_PWD, $storeScope, $storeId);
        $this->merchantId = $merchantId ? $merchantId : $this->_scopeConfig->getValue(FloaScopeConfigInterface::MERCHANT_ID, $storeScope, $storeId);
        $this->env = $this->_scopeConfig->getValue(FloaScopeConfigInterface::ENV, $storeScope, $storeId);
        $this->token = $this->_scopeConfig->getValue(FloaScopeConfigInterface::TOKEN);
        $this->tokenUpdateDate = $this->_scopeConfig->getValue(FloaScopeConfigInterface::TOKEN_UPDATE);
        $this->tokenEnv = $this->_scopeConfig->getValue(FloaScopeConfigInterface::TOKEN_ENV);
        $this->debug = $this->_scopeConfig->getValue(FloaScopeConfigInterface::DEBUGMODE);
        $this->active = $this->_scopeConfig->getValue('payment/floa_payment/' . $this->method . '_active', $storeScope, $storeId)
                                            && $this->_scopeConfig->getValue(FloaScopeConfigInterface::IS_ACTIVE_PATH, $storeScope, $storeId);
        $this->merchantSiteId = $merchantSiteId ? $merchantSiteId : $this->_scopeConfig->getValue('payment/floa_payment/' . $this->method . '_merchant_site_id', $storeScope, $storeId);
        $this->authorization = base64_encode($this->merchantLogin . ':' . $this->merchantPassword);
        $this->contextPaymentUri = $this->env != 'INTE' ? self::PAYMENT_GATEWAY_PROD : self::PAYMENT_GATEWAY_INTE;
        $this->contextEligibilityUri = $this->env != 'INTE' ? self::ELIGIBILITY_PROD : self::ELIGIBILITY_INTE;
        $this->contextServicesUri = $this->env != 'INTE' ? self::SERVICES_PROD : self::SERVICES_INTE;
        $this->defaultPaymentOptionRef = isset(self::paymentMethodsAvailable()[$this->method]) ? self::paymentMethodsAvailable()[$this->method]['paymentOptionRef'] : false;
        $this->timeoutMs = 3000;
        $this->contextCheck = $this->checkConfig();
        $this->logger = null;

        return $this->contextCheck;
    }

    /**
     * PaymentMethodsAvailable
     *
     * @return mixed
     */
    public static function paymentMethodsAvailable()
    {
        return [
            'cb3x' => ['ech' => '3',  'lib' => '3X',  'floapay', 'paymentOptionRef' => 81],
            'cb4x' => ['ech' => '4',  'lib' => '4X',  'floapay', 'paymentOptionRef' => 63],
            'cb10x' => ['ech' => '10', 'lib' => '10X', 'floapay', 'paymentOptionRef' => 98],
            'cb1xd' => ['ech' => '1',  'lib' => '1XD', 'floapay', 'paymentOptionRef' => 94],
        ];
    }

    /**
     * CheckConfig
     *
     * Check the context configuration
     *
     * @return array
     */
    public function checkConfig()
    {
        $configValid = true;
        $errors = [];
        if (!$this->merchantSiteId) {
            $configValid = false;
            $errors[] = 'The Merchant Site ID is invalid.';
        }
        if (!$this->merchantId) {
            $configValid = false;
            $errors[] = 'The Merchant ID is invalid.';
        }
        if (!$this->active) {
            $configValid = false;
            $errors[] = 'The Merchant Site ID is not active.';
        }
        if (!$this->merchantLogin || !$this->merchantPassword) {
            $configValid = false;
            $errors[] = 'The credentials are invalids.';
        }
        if ($configValid) {
            $tokenContext = $this->getSecurityToken();
            if ($tokenContext['state'] == false && $configValid) {
                $configValid = false;
                $errors[] = $tokenContext['message'];
            }
            $paymentOptionsContext = $this->getPaymentOptions();
            if (is_array($paymentOptionsContext)) {
                if ($paymentOptionsContext['state'] == false && $tokenContext['state'] == true) {
                    $configValid = false;
                    $errors[] = $paymentOptionsContext['message'];
                }
            }
        }

        return ['state' => $configValid, 'message' => $errors];
    }

    /**
     * GetSecurityToken
     *
     * Create a security Token STS
     *
     * @return array
     */
    private function getSecurityToken()
    {
        if (!empty($this->token) && $this->tokenEnv == $this->env) {
            if ((time() - $this->tokenUpdateDate) < 3600) {
                return ['state' => true, 'token' => $this->token];
            }
        }
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/auth/token');
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authorization: ' . $this->authorization,
        ]);
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $isTokenValid = preg_match('/^"[0-9a-f]{32}"$/', $body);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200 && $isTokenValid) {
            $this->token = str_replace('"', '', $body);
            $this->tokenUpdateDate = time();
            $this->tokenEnv = $this->env;
            $this->_configWriter->save(FloaScopeConfigInterface::TOKEN, $this->token);
            $this->_configWriter->save(FloaScopeConfigInterface::TOKEN_UPDATE, $this->tokenUpdateDate);
            $this->_configWriter->save(FloaScopeConfigInterface::TOKEN_ENV, $this->env);
            $this->_cacheManager->flush(['config']);

            return ['state' => true, 'token' => $this->token];
        } else {
            $message = '';
            switch ($httpcode) {
                case 403:
                    $message = '[TOKEN] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[TOKEN] Internal server error on FLOA environnement.';
                    break;
                case 400:
                    $message = '[TOKEN] You have send a bad request on FLOA environnement.';
                    break;
                default:
                    $message = '[TOKEN] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * GetEligibility
     *
     * Get the eligibility of a customer
     *
     * @param Quote $cart
     * @param Quote\Address $shippingAddress
     * @param Quote\Address $billingAddress
     * @param array $eligibilityDatas
     * @param int $reportDelayInDays
     *
     * @return array
     */
    public function getEligibility($cart, $shippingAddress, $billingAddress, $eligibilityDatas, $reportDelayInDays = 0)
    {
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_POST, true);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextEligibilityUri . 'api/v4/eligibilities/?merchantId=' . $this->merchantId . '&merchantSiteIds=' . $this->merchantSiteId);
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $floaTools = new FloaTools();
        $orderRef = $floaTools->generateOrderRef($this->method, $cart->getId());
        $eligibilityRequest = [
            'presaleFolder' => [
                'rawAmount' => $floaTools->convertToInt($cart->getGrandTotal()),
                'shoppingCarts' => $this->eligibilityHelper->shoppingCarts($cart, $shippingAddress, $orderRef),
                'merchantSite' => $this->eligibilityHelper->merchantSite($cart),
                'customer' => $this->eligibilityHelper->customer($billingAddress, $eligibilityDatas),
                'shippingAddress' => $this->eligibilityHelper->address($shippingAddress),
                'billingAddress' => $this->eligibilityHelper->address($billingAddress),
                'saleChannel' => $this->eligibilityHelper->saleChannel(),
                'isSecured' => true,
                'reportDelayInDays' => $reportDelayInDays,
            ],
        ];

        $this->curlSetopt($ch, CURLOPT_POSTFIELDS, json_encode($eligibilityRequest));
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->eligibilities[0]->hasAgreement) && $responseJson->eligibilities[0]->hasAgreement == true) {
                return [
                    'state' => true,
                    'datas' => [
                        'token' => $responseJson->eligibilities[0]->token,
                        'hasAgreement' => $responseJson->eligibilities[0]->hasAgreement,
                        'totalAmount' => $responseJson->eligibilities[0]->totalAmount,
                        'requestId' => $responseJson->eligibilities[0]->requestId,
                        'orderRef' => $orderRef,
                        'links' => $responseJson->eligibilities[0]->links,
                    ],
                ];
            } else {
                if (isset($responseJson->eligibilities[0]->errors[0])) {
                    $error = $responseJson->eligibilities[0]->errors[0];
                } else {
                    $error = '';
                }

                return [
                    'state' => true,
                    'datas' => [
                        'hasAgreement' => false,
                        'requestId' => $responseJson->eligibilities[0]->requestId,
                        'orderRef' => $orderRef,
                        'error' => $error,
                    ],
                ];
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[ELIGIBILITY] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[ELIGIBILITY] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[ELIGIBILITY] Your credentials are incorrect, access denied.';
                    break;
                case 406:
                    $message = '[ELIGIBILITY] Your request is not acceptable.';
                    break;
                case 500:
                    $message = '[ELIGIBILITY] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[ELIGIBILITY] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * GetPaymentSchedules
     *
     * Get information form an existing Order
     *
     * @param string $orderRef
     * @param int $merchantId
     * @param int $merchantSiteId
     *
     * @return array
     */
    public function getPaymentSchedules($orderRef, $merchantId = false, $merchantSiteId = false)
    {
        $merchantId = $merchantId ? $merchantId : $this->merchantId;
        $merchantSiteId = $merchantSiteId ? $merchantSiteId : $this->merchantSiteId;
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/payments/' . $orderRef . '/merchants/' . $merchantId . '/sites/' . $merchantSiteId);
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->responseCode) && $responseJson->responseCode == 'success') {
                return ['state' => true, 'schedules' => $responseJson->schedules];
            } else {
                return [
                    'state' => false,
                    'message' => '[PAYMENT SCHEDULES] No more information.',
                ];
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[PAYMENT SCHEDULES] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[PAYMENT SCHEDULES] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[PAYMENT SCHEDULES] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[PAYMENT SCHEDULES] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[PAYMENT SCHEDULES] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * Create a payment session
     *
     * @param Quote $cart
     * @param Quote\Address $shippingAddress
     * @param Quote\Address $billingAddress
     * @param array $scoringDatas
     * @param int $reportDelayInDays
     *
     * @return array
     */
    public function createPaymentSession($cart, $shippingAddress, $billingAddress, $scoringDatas, $reportDelayInDays = 0)
    {
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_POST, true);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/payment-sessions');
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $bodyInfo = [
            'merchantId' => $this->merchantId,
            'merchantSiteId' => $this->merchantSiteId,
            'customer' => $this->paymentHelper->customer($billingAddress, $shippingAddress, $cart->getCustomerId() ? $cart->getCustomerId() : $billingAddress->getEmail()),
            'orderData' => $this->paymentHelper->orderData($shippingAddress, $scoringDatas, $cart),
            'configuration' => $this->paymentHelper->configuration($cart, $this->defaultPaymentOptionRef, $reportDelayInDays),
        ];

        $this->curlSetopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyInfo));
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->operationSucceeded) && $responseJson->operationSucceeded == true) {
                return ['state' => true, 'session' => $responseJson];
            } else {
                return [
                    'state' => false,
                    'message' => '[PAYMENT SESSION] No more information.',
                ];
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[PAYMENT SESSION] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[PAYMENT SESSION] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[PAYMENT SESSION] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[PAYMENT SESSION] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[PAYMENT SESSION] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * GetPaymentResult
     *
     * Get result of an existing payment request
     *
     * @param string $payemntSessionID
     *
     * @return array
     */
    public function getPaymentResult($payemntSessionID)
    {
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/payment-sessions/' . $payemntSessionID . '/paymentResult/');
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body, true);

            return [
                'state' => true,
                'datas' => $responseJson,
            ];
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[PAYMENT RESULT] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[PAYMENT RESULT] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[PAYMENT RESULT] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[PAYMENT RESULT] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[PAYMENT RESULT] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * GetPaymentResultFromPaymentSchedules
     *
     * @param mixed $orderRef
     * @param mixed $merchantId
     * @param mixed $merchantSiteId
     *
     * @return void
     */
    public function getPaymentResultFromPaymentSchedules($orderRef, $merchantId = false, $merchantSiteId = false)
    {
        $schedules = $this->getPaymentSchedules($orderRef, $merchantId, $merchantSiteId);
        if (isset($schedules['state']) && $schedules['state'] == true && isset($schedules['schedules'])) {
            $totalAmount = 0;
            foreach ($schedules['schedules'] as $schedule) {
                $totalAmount += $schedule->amount;
                if ($schedule->rank == 1) {
                    $state = $schedule->state;
                }
            }
            if (isset($state)) {
                switch ($state) {
                    case 'cancelled':
                    case 'init':
                        $paymentResultCode = 2;
                        break;
                    case 'payed':
                    case 'cashed':
                        $paymentResultCode = 0;
                        break;
                    default:
                        $paymentResultCode = 3;
                        break;
                }
            } else {
                $paymentResultCode = 3;
            }

            return [
                'state' => true,
                'datas' => [
                    'amount' => $totalAmount,
                    'paymentResultCode' => $paymentResultCode,
                ],
            ];
        } else {
            return [
                'state' => false,
            ];
        }
    }

    /**
     * GetPaymentOptions
     *
     * Get the payments options
     *
     * @return array
     */
    private function getPaymentOptions()
    {
        $responseJson = $this->getCache('', 'paymentoption', null, 'paymentoption' . $this->method);
        if ($responseJson === false) {
            $ch = $this->curlInit();
            $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
            $this->curlSetopt($ch, CURLOPT_HEADER, true);
            $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/payment-options/merchants/' . $this->merchantId . '/sites/' . $this->merchantSiteId);
            $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
                'authToken: ' . $this->token,
            ]);
            $response = $this->curlExec($ch);
            if (curl_errno($ch)) {
                $error_code = curl_errno($ch);
                if ($error_code == CURLE_OPERATION_TIMEOUTED) {
                    throw new \Exception('Error timeout CURL - we do not call any other payments options');
                }
            }
            $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $header_size);
            $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode !== 200) {
                $message = '';
                switch ($httpcode) {
                    case 400:
                        $message = '[PAYMENT OPTIONS] You have send a bad request on FLOA environnement.';
                        break;
                    case 401:
                        $message = '[PAYMENT OPTIONS] You are not authorized to make this request on FLOA environnement.';
                        break;
                    case 403:
                        $message = '[PAYMENT OPTIONS] Your credentials are incorrect, access denied.';
                        break;
                    case 500:
                        $message = '[PAYMENT OPTIONS] Internal server error on FLOA environnement.';
                        break;
                    default:
                        $message = '[PAYMENT OPTIONS] Unknown error on FLOA environment.';
                        break;
                }
                $this->addLog('Error - ' . $message);
                $this->setCache(1800,  '', 'paymentoption', [
                    'state' => false,
                    'message' => 'Timeout Error',
                ]);
                return [
                    'state' => false,
                    'message' => $message,
                ];
            }
            $responseJson = json_decode($body);
            $this->addLog('Setting payment options in cache');
            $this->setCache(86400, '', 'paymentoption', $responseJson, 'paymentoption' . $this->method);
        }

        if (isset($responseJson->paymentOptions)) {
            foreach ($responseJson->paymentOptions as $paymentOption) {
                if ($paymentOption->paymentOptionRef == $this->defaultPaymentOptionRef) {
                    $this->paymentOption = $paymentOption;
                    $this->minAmount = $paymentOption->minAmount / 100;
                    $this->maxAmount = $paymentOption->maxAmount / 100;

                    return ['state' => true, 'paymentOption' => $this->paymentOption];
                }
            }
        } else {
            return [
                'state' => false,
                'message' => '[PAYMENT OPTIONS] No more information.',
            ];
        }
    }

    /**
     * GetEstimatedSchedule
     *
     * Get the estimated installment plan
     *
     * @param float $amount
     * @param bool $reportDelayInDays
     *
     * @return array
     */
    public function getEstimatedSchedule($amount, $reportDelayInDays = false, $inCheckout = false)
    {
        $floaTools = new FloaTools();
        $amount = $floaTools->convertToInt($amount);

        $cacheType = ($inCheckout === true ? 'checkout' : 'schedule') . $this->method;
        $cacheTimeout = 900;

        $this->cacheEnabled = (bool) $this->deploymentConfig->get('cache_types/floaoffers');
        if ($inCheckout === false) {
            $cacheTimeout = 1800;
        }
        $cacheResponse = $this->getCache($amount, $cacheType);
        if ($cacheResponse !== false) {
            $this->addLog("Estimated {$cacheType} - cache found for $amount - ($this->method)", false);

            return [
                'state' => true,
                'amount' => $cacheResponse->amount,
                'schedules' => $cacheResponse->schedules,
                'fees' => ($cacheResponse->amount - $amount),
            ];
        }

        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_POST, true);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/estimated-schedule/merchants/' . $this->merchantId . '/sites/' . $this->merchantSiteId);
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $content = [
            'amount' => $amount,
            'includePaymentFees' => true,
            'orderDate' => date('Y-m-d') . 'T' . date('H:i:s') . '+00:00',
            'paymentOptionRef' => $this->defaultPaymentOptionRef,
        ];
        if ($reportDelayInDays !== false) {
            $content['ReportDelayInDays'] = $reportDelayInDays;
        }
        $this->curlSetopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $curlErrorCode = curl_errno($ch);

        $error_code = curl_errno($ch);
        if ((int) $error_code > 0) {
            if ($error_code == CURLE_OPERATION_TIMEOUTED) {
                throw new \Exception('Error timeout CURL - we do not call any other offers');
            }
        }

        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);

        if ($this->cacheEnabled !== false) {
            $this->addLog("Estimated {$cacheType} - cache not found for $amount - ($this->method)", false);
            $this->addLog("Estimated {$cacheType} - " . json_encode($body), false);
        }

        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->code) && $responseJson->code == 0) {
                $this->setCache($cacheType === 'checkout' ? 3600 : 86400, $amount, $cacheType, $responseJson);

                return [
                    'state' => true,
                    'amount' => $responseJson->amount,
                    'schedules' => $responseJson->schedules,
                    'fees' => ($responseJson->amount - $amount),
                ];
            } else {
                return [
                    'state' => false,
                    'message' => '[ESTIMATED SCHEDULE] ' . (isset($responseJson->message) ? $responseJson->message : 'No more information.'),
                ];

                $this->setCache($cacheTimeout,  $amount, $cacheType, [
                    'state' => false,
                    'message' => $message,
                ]);
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[ESTIMATED SCHEDULE] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[ESTIMATED SCHEDULE] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[ESTIMATED SCHEDULE] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[ESTIMATED SCHEDULE] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[ESTIMATED SCHEDULE] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            $this->setCache($cacheTimeout,  $amount, $cacheType, [
                'state' => false,
                'message' => $message,
            ]);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * UpdateOrder
     *
     * Update the total amount of an Order
     *
     * @param string $orderRef
     * @param string $scoringToken
     * @param int $oldAmount
     * @param int $newAmount
     * @param int $merchantId
     * @param int $merchantSiteId
     *
     * @return array
     */
    public function updateOrder($orderRef, $scoringToken, $oldAmount, $newAmount, $merchantId, $merchantSiteId)
    {
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_POST, true);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextServicesUri . 'UpdateOrder');
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $content = [
            'headerMessage' => [
                'Context' => [
                    'MerchantId' => $merchantId,
                    'MerchantSiteId' => $merchantSiteId,
                ],
                'SecurityContext' => [
                    'DomainRightsList' => null,
                    'IssuerID' => null,
                    'SessionID' => null,
                    'SubjectLocality' => null,
                    'TokenId' => $this->token,
                    'UserName' => null,
                ],
                'Localization' => [
                    'Language' => 'FR',
                    'Currency' => 'EUR',
                    'Country' => 'FR',
                    'DecimalPosition' => 2,
                ],
                'Version' => '1.0',
            ],
            'updateOrderRequestMessage' => [
                'OrderRef' => $orderRef,
                'ScoringToken' => $scoringToken,
                'OldAmount' => $oldAmount,
                'NewAmount' => $newAmount,
            ],
        ];

        $this->curlSetopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->UpdateOrderResult)
                && isset($responseJson->UpdateOrderResult->ResponseCode)
                && ($responseJson->UpdateOrderResult->ResponseCode == 0 || $responseJson->UpdateOrderResult->ResponseCode == 4)) {
                return [
                    'state' => true,
                    'schedules' => $responseJson->UpdateOrderResult->Schedule,
                    'message' => $responseJson->UpdateOrderResult->ResponseMessage,
                ];
            } else {
                return [
                    'state' => false,
                    'message' => '[UPDATE ORDER] ' . isset($responseJson->UpdateOrderResult->ResponseMessage) ? $responseJson->UpdateOrderResult->ResponseMessage : 'No more information',
                ];
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[UPDATE ORDER] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[UPDATE ORDER] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[UPDATE ORDER] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[UPDATE ORDER] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[UPDATE ORDER] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * PayOrderRank
     *
     * Capture a valid Order
     *
     * @param string $orderRef
     * @param int $merchantId
     * @param int $merchantSiteId
     *
     * @return array
     */
    public function payOrderRank($orderRef, $merchantId, $merchantSiteId)
    {
        $ch = $this->curlInit();
        $this->curlSetopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $this->curlSetopt($ch, CURLOPT_HEADER, true);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $this->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $this->curlSetopt($ch, CURLOPT_POST, true);
        $this->curlSetopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetopt($ch, CURLOPT_URL, $this->contextPaymentUri . 'v1/payments/' . $orderRef . '/operations/capture');
        $this->curlSetopt($ch, CURLOPT_HTTPHEADER, [
            'authToken: ' . $this->token,
            'Content-Type: application/json',
            'accept: application/json',
        ]);
        $content = [
            'merchantId' => $merchantId,
            'merchantSiteId' => $merchantSiteId,
            'attempt' => 1,
            'rank' => 1,
        ];
        $this->curlSetopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->curlSetopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        $response = $this->curlExec($ch);
        $header_size = $this->curlgetInfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        $httpcode = $this->curlgetInfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == 200) {
            $responseJson = json_decode($body);
            if (isset($responseJson->responseCode)
                && $responseJson->responseCode == 'success'
                && $responseJson->actionType == 'capture') {
                return [
                    'state' => true,
                    'orderRef' => $responseJson->orderRef,
                ];
            } else {
                return [
                    'state' => false,
                    'message' => '[CAPTURE ORDER] ' . isset($responseJson->responseMessage) ? $responseJson->responseMessage : 'No more information',
                ];
            }
        } else {
            $message = '';
            switch ($httpcode) {
                case 400:
                    $message = '[CAPTURE ORDER] You have send a bad request on FLOA environnement.';
                    break;
                case 401:
                    $message = '[CAPTURE ORDER] You are not authorized to make this request on FLOA environnement.';
                    break;
                case 403:
                    $message = '[CAPTURE ORDER] Your credentials are incorrect, access denied.';
                    break;
                case 500:
                    $message = '[CAPTURE ORDER] Internal server error on FLOA environnement.';
                    break;
                default:
                    $message = '[CAPTURE ORDER] Unknown error on FLOA environment.';
                    break;
            }
            $this->addLog('Error - ' . $message);

            return [
                'state' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * CurlSetopt
     *
     * @param mixed $ch
     * @param mixed $item
     * @param mixed $value
     *
     * @return void
     */
    private function curlSetopt($ch, $item, $value)
    {
        curl_setopt($ch, $item, $value);
    }

    /**
     * CurlgetInfo
     *
     * @param mixed $ch
     * @param mixed $value
     *
     * @return mixed
     */
    private function curlgetInfo($ch, $value)
    {
        return curl_getinfo($ch, $value);
    }

    /**
     * CurlInit
     *
     * @param mixed $ch
     * @param mixed $value
     *
     * @return resource|false|CurlHandle
     */
    private function curlInit()
    {
        return curl_init();
    }

    /**
     * CurlExec
     *
     * @param mixed $ch
     *
     * @return bool|string
     */
    private function curlExec($ch)
    {
        return curl_exec($ch);
    }

    /**
     * getCache
     *
     * @param float $amount
     * @param string $type
     *
     * @return mixed
     */
    public function getCache($amount, $type, $returnArray = null, $cacheKey = null)
    {
        if ($this->cacheEnabled === false) {
            return false;
        }
        $cachedApiError = $this->cache->load('apiError');
        if (empty($cachedApiError) === false) {
            return [];
        }
        if ($cacheKey === null) {
            $cacheKey = \FLOA\Payment\Model\Cache\Offers::TYPE_IDENTIFIER . $type . $amount;
        }
        $cached = $this->cache->load($cacheKey);
        if (empty($cached) === true) {
            return false;
        }

        return json_decode($cached, $returnArray);
    }

    /**
     * setCache
     *
     * @param float $amount
     * @param string $type
     *
     * @return mixed
     */
    public function setCache($cacheTime, $amount, $type, $cacheData, $cacheKey = null)
    {
        if ($this->cacheEnabled === false) {
            return false;
        }
        if ($cacheKey === null) {
            $cacheKey = \FLOA\Payment\Model\Cache\Offers::TYPE_IDENTIFIER . $type . $amount;
        }
        $cacheTag = \FLOA\Payment\Model\Cache\Offers::CACHE_TAG;

        $storeData = $this->cache->save(
            $this->serializer->serialize($cacheData),
            $cacheKey,
            [$cacheTag],
            $cacheTime
        );

        return $storeData;
    }

    /**
     * addLog - Add log when debug mode
     *
     * @return void
     */
    public function addLog($message, $logError = true)
    {
        $logErrorOrDebug = (bool) $this->debug === true || $logError === true;
        if ($logErrorOrDebug === true && $this->logger === null) {
            $this->logger = new FloaPayLogger();
        }
        if ($this->logger !== null) {
            $this->logger->info('FloaPayManagement - ' . $message);
        }
    }
}
