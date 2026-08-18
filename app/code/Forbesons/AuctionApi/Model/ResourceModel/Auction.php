<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Auction extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('manage_auction', 'auction_id');
    }
}