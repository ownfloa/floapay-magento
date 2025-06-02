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

use FLOA\Payment\Helpers\FloaTools;
use FLOA\Payment\Model\Config\FloaScopeConfigInterface;
use FLOA\Payment\Model\FormValidation\Config as FormConfig;
use IntlDateFormatter;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;

class FloaPayConfigProvider implements ConfigProviderInterface
{
    public const PRIVACY_POLICY_LINKS = [
        'FR' => 'https://www.floabank.fr/politique-confidentialite',
        'ES' => 'https://www.floapay.com/images/PDF/20210614_POLITIQUE_CONFIDENTIALITE_FLOA_BANK_vES.pdf',
        'BE' => 'https://www.floapay.com/images/PDF/Politique_de_confidentialit_BELG.pdf',
        'PT' => 'https://www.floapay.com/images/PDF/Politique_de_confidentialit_EN_pour_PORT.pdf',
        'IT' => 'https://www.floapay.com/images/PDF/Privacy_Policy_IT.pdf',
        'default' => 'https://www.floapay.com/images/PDF/20210330_Politique_de_confidentialit_FLOA_Bank_EN.pdf',
    ];

    public const CGV_LINKS = [
        'FR' => 'https://www.floabank.fr/conditions-generales-paiement-plusieurs-fois',
        'ES' => 'https://www.floapay.com/images/PDF/20210118_CGV_Espagne_vdef.pdf',
        'BE' => 'https://www.floabank.fr/images/pdf/CB4X/CGV_BELGIQUE/CGV_BELG_EN_FR_NL.pdf',
        'PT' => 'https://www.floapay.com/images/PDF/CGS_General_Conditions_Portugal_FLOA.pdf',
        'IT' => 'https://www.floapay.com/images/PDF/GTC_FLOAPAY_Italy_bilingue.pdf',
        'default' => 'https://www.floapay.com/images/PDF/20210330_Politique_de_confidentialit_FLOA_Bank_EN.pdf',
    ];

    /** @var string */
    protected $_storeScope;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var int */
    protected $_store_id;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    /** @var \Magento\Framework\Locale\ResolverInterface */
    protected $localeResolver;

    /** @var \FLOA\Payment\Model\FloaPayManagement */
    protected $floaPayManagement;

    /** @var \Magento\Framework\Pricing\Helper\Data */
    protected $priceHelper;

    /** @var \FLOA\Payment\Model\FormValidation\Config */
    protected $formConfig;

    /** @var Repository */
    protected $assetRepository;

    /** @var Resolver */
    protected $resolver;

    /** @var float */
    protected $currentTotal;

    /**
     * Construct
     *
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $localeResolver
     * @param FloaPayManagement $floaPayManagement
     * @param PriceHelper $priceHelper
     * @param FormConfig $formConfig
     * @param Resolver $resolver
     *
     * @return void
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        ResolverInterface $localeResolver,
        FloaPayManagement $floaPayManagement,
        PriceHelper $priceHelper,
        FormConfig $formConfig,
        Repository $assetsRepository,
        Resolver $resolver
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->localeResolver = $localeResolver;
        $this->floaPayManagement = $floaPayManagement;
        $this->priceHelper = $priceHelper;
        $this->formConfig = $formConfig;
        $this->assetRepository = $assetsRepository;
        $this->resolver = $resolver;
        $this->_storeScope = ScopeInterface::SCOPE_STORE;
        $this->_storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->_scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->_store_id = $this->_storeManager->getStore()->getId();
        $this->currentTotal = $this->checkoutSession->getQuote()->getGrandTotal();
    }

    /**
     * GetConfig
     *
     * @return void
     */
    public function getConfig()
    {
        $countryCode = $this->_scopeConfig->getValue(FloaScopeConfigInterface::COUNTRY_CODE_PATH, $this->_storeScope, $this->_store_id);
        $defType = $this->_scopeConfig->getValue(FloaScopeConfigInterface::DEF_TYPE_PATH, $this->_storeScope, $this->_store_id);
        $defDelay = $this->_scopeConfig->getValue(FloaScopeConfigInterface::DEF_DELAY_PATH, $this->_storeScope, $this->_store_id);
        $defDateDelay = date('Y-m-d', strtotime('+' . $defDelay . ' days'));
        $minDefDate = date('Y-m-d', strtotime('+1 day'));
        $maxDefDate = date('Y-m-d', strtotime('+30 days'));
        $maxBirthDate = date('Y-m-d', strtotime('-18 years'));
        $debugMode = $this->_scopeConfig->getValue(FloaScopeConfigInterface::DEBUGMODE, $this->_storeScope, $this->_store_id);

        return [
            'payment' => [
                'floa' => [
                    'countryCode' => $countryCode,
                    'defType' => $defType,
                    'defDelay' => $defDelay,
                    'defDateDelay' => $defDateDelay,
                    'minDefDate' => $minDefDate,
                    'maxDefDate' => $maxDefDate,
                    'maxBirthDate' => $maxBirthDate,
                    'total' => (new FloaTools())->convertToInt($this->currentTotal),
                    'formValidation' => $this->formConfig->getJsFormCustomValidations($countryCode),
                    'cgv_link' => $this->getCgvLink($countryCode),
                    'plans' => $this->getAvailablePlans(),
                    'mainTitle' => __('Pay in multiple times with '),
                    'debug' => (bool) $debugMode,
                ],
            ],
        ];
    }

    /**
     * GetCgvConfig
     *
     * @return string[]
     */
    private function getCgvConfig()
    {
        return self::CGV_LINKS;
    }

    /**
     * GetCgvLink
     *
     * @param mixed $iso
     *
     * @return void
     */
    private function getCgvLink($iso)
    {
        $cgvConf = $this->getCgvConfig();

        return isset($cgvConf[$iso]) ? $cgvConf[$iso] : $cgvConf['default'];
    }

    public function getAvailablePlans()
    {
        $plans = [];
        $isFirstPlan = true;
        $floaTools = new FloaTools();
        try {
            foreach ($this->floaPayManagement->paymentMethodsAvailable() as $onePaymentMethod => $contentPayment) {
                $methodPayment = $onePaymentMethod;
                $cacheResponse = $this->floaPayManagement->getCache(
                    $floaTools->convertToInt($this->currentTotal),
                    'checkout' . $onePaymentMethod
                );
                if ($cacheResponse !== false) {
                    if (isset($cacheResponse->amount) === false || isset($cacheResponse->schedules) == false) {
                        continue;
                    }
                    $estimatedPlan = [
                        'state' => true,
                        'amount' => $cacheResponse->amount,
                        'schedules' => $cacheResponse->schedules,
                        'fees' => $cacheResponse->amount - $floaTools->convertToInt($this->currentTotal),
                    ];
                    $this->floaPayManagement->addLog(
                        "Estimated checkout{$onePaymentMethod} - cache found for {$this->currentTotal} - ($onePaymentMethod)",
                        false
                    );
                    $this->buildEstimatedPlan($estimatedPlan, $floaTools, $onePaymentMethod, $plans, $isFirstPlan);
                } else {
                    $contextCheck = $this->floaPayManagement->initialize($methodPayment, $this->_storeScope, $this->_store_id);
                    if ($contextCheck['state'] == true) {
                        $reportDelayInDays = false;
                        if ($methodPayment == 'cb1xd') {
                            $reportMode = $this->_scopeConfig->getValue('payment/floa_payment/cb1xd_report_mode', $this->_storeScope, $this->_store_id);
                            $reportDelay = $this->_scopeConfig->getValue('payment/floa_payment/cb1xd_report_delay', $this->_storeScope, $this->_store_id);
                            $reportDelayInDays = ($reportMode == 1) ? 30 : ($reportMode == 2 ? $reportDelay : false);
                        }
                        $estimatedPlan = $this->floaPayManagement->getEstimatedSchedule(
                            $this->currentTotal,
                            $reportDelayInDays,
                            true
                        );
                        $this->buildEstimatedPlan($estimatedPlan, $floaTools, $onePaymentMethod, $plans, $isFirstPlan);
                    } else {
                        $plans[$onePaymentMethod] = null;
                    }
                }
            }
        } catch (\Exception $ex) {
            (new FloaPayLogger())->info('FloaPayConfigProvider - getAvailablePlans - ' . $ex->getMessage());
            (new FloaPayLogger())->info('FloaPayConfigProvider - getAvailablePlans - ' . $ex->getTraceAsString());
        }


        return $plans;
    }

    private function buildEstimatedPlan($estimatedPlan, $floaTools, $onePaymentMethod, &$plans, &$isFirstPlan)
    {
        if ($estimatedPlan['state'] == true) {
            $logo = $this->assetRepository
                    ->createAsset('FLOA_Payment::images/logos/new_floa_' . strtoupper($onePaymentMethod) . '_tiny.svg')
                    ->getUrl();
            $logoMobile = $this->assetRepository
                    ->createAsset('FLOA_Payment::images/logos/new_floa_' . strtoupper($onePaymentMethod) . '_mobile.svg')
                    ->getUrl();

            $plans[$onePaymentMethod] = [
                'amount' => $this->priceHelper->currency(
                    $floaTools->convertToFloat($estimatedPlan['amount']),
                    true,
                    false
                ),
                'amountNoFees' => $this->priceHelper->currency(
                    $floaTools->convertToFloat($estimatedPlan['amount'] - $estimatedPlan['fees']),
                    true,
                    false
                ),
                'fees' => $this->priceHelper->currency(
                    $floaTools->convertToFloat($estimatedPlan['fees']),
                    true,
                    false
                ),
                'rawFees' => $estimatedPlan['fees'],
                'isFirstPlan' => $isFirstPlan,
                'isLastPlan' => false,
                'logo' => $logo,
                'logoMobile' => $logoMobile,
                'code' => $onePaymentMethod,
                'echs' => (int) $onePaymentMethod,
                'schedules' => [],
            ];
            $isFirstPlan = false;
            $key = 0;
            foreach ($estimatedPlan['schedules'] as $schedule) {
                $localeContext = $this->resolver->getLocale();

                $fmt = new IntlDateFormatter(
                    $localeContext,
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::FULL
                );
                $fmt->setPattern('d MMMM Y');

                $schedule->date_formatted = $fmt->format(strtotime($schedule->date));

                $plans[$onePaymentMethod]['schedules'][] = [
                    'amount' => $this->priceHelper->currency($floaTools->convertToFloat($schedule->amount), true, false),
                    'date' => date('d/m/Y', strtotime($schedule->date)),
                    'date_formatted' => $schedule->date_formatted,
                    'key' => $key,
                ];
                ++$key;
            }
        }
    }
}
