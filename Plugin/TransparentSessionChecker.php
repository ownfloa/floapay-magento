<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
 
namespace FLOA\Payment\Plugin;
 
use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;
 
/**
 * Intended to preserve session cookie after submitting POST form from FLOA to Magento controller.
 */
class TransparentSessionChecker
{
    const TRANSPARENT_REDIRECT_PATH = 'floa/payment/validate'; //The path to the controller that handles your return
 
    /**
     * @var Http
     */
    private $request;
 
    /**
     * @param Http $request
     */
    public function __construct(
        Http $request
    ) {
        $this->request = $request;
    }
 
    /**
     * Prevents session starting while instantiating FLOA transparent redirect controller.
     *
     * @param SessionStartChecker $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheck(SessionStartChecker $subject, bool $result): bool
    {
        if ($result === false) {
            return false;
        }
        return strpos((string) $this->request->getPathInfo(), self::TRANSPARENT_REDIRECT_PATH) === false;
    }
}