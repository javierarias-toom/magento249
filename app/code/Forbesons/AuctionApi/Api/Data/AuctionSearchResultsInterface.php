<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api\Data;

interface AuctionSearchResultsInterface
{
    /**
     * @return \Forbesons\AuctionApi\Api\Data\AuctionInterface[]
     */
    public function getItems();

    /**
     * @param \Forbesons\AuctionApi\Api\Data\AuctionInterface[] $items
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
