<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model\ResourceModel\Auction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'auction_id';

    protected function _construct()
    {
        $this->_init(
            \Forbesons\AuctionApi\Model\Auction::class,
            \Forbesons\AuctionApi\Model\ResourceModel\Auction::class
        );
    }
}