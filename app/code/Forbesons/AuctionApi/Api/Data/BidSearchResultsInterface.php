<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api\Data;

interface BidSearchResultsInterface
{
    /**
     * @return \Forbesons\AuctionApi\Api\Data\BidInterface[]
     */
    public function getItems();

    /**
     * @param \Forbesons\AuctionApi\Api\Data\BidInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return int
     */
    public function getTotalCount();

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount);
}
