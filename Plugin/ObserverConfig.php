<?php

namespace FLOA\Payment\Plugin;

use FLOA\Payment\Model\FloaPayLogger;
use FLOA\Payment\Model\Cache\Offers;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

class ObserverConfig
{
    protected $cacheTypeList;
    protected $cacheFrontendPool;
 
    public function __construct(TypeListInterface $cacheTypeList, 
    Pool $cacheFrontendPool)
    {
    
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        $section = $subject->getSection();
        $data = $subject->getData();

        if ($section !== 'payment') {
            return $proceed();
        }

        if (isset($data['groups']['floa_payment']) === false) {
            return $proceed();
        }

        $_types = [
            strtolower(Offers::CACHE_TAG),
        ];
    
        foreach ($_types as $type) 
        {
            $this->cacheTypeList->cleanType($type);
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) 
        {
            $cacheFrontend->getBackend()->clean();
        }

        return $proceed();
    }
}