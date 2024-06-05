<?php

namespace FLOA\Payment\Model\Cache;

class Offers extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    /**
     * Type Code for Cache
     */
    const TYPE_IDENTIFIER = 'floaofferscache';

    /**
     * Tag of Cache
     */
    const CACHE_TAG = 'FLOAOFFERS';

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     */
    public function __construct(
        \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
    ) {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
