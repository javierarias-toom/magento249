<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model\ResourceModel\Bid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'bid_id';

    protected function _construct()
    {
        $this->_init(
            \Forbesons\AuctionApi\Model\Bid::class,
            \Forbesons\AuctionApi\Model\ResourceModel\Bid::class
        );
    }
}