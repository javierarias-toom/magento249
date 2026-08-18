<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\Data\AuctionSearchResultsInterface;

class AuctionSearchResults implements AuctionSearchResultsInterface
{
    /**
     * @var \Forbesons\AuctionApi\Api\Data\AuctionInterface[]
     */
    private array $items = [];

    private int $totalCount = 0;

    public function getItems()
    {
        return $this->items;
    }

    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    public function getTotalCount()
    {
        return $this->totalCount;
    }

    public function setTotalCount($totalCount)
    {
        $this->totalCount = (int)$totalCount;
        return $this;
    }
}