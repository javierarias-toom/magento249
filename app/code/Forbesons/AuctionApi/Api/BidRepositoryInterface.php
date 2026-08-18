<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api;

use Forbesons\AuctionApi\Api\Data\BidSearchResultsInterface;

interface BidRepositoryInterface
{
    /**
     * Get a paginated list of bids for a given auction, newest first.
     *
     * @param int $id Auction id
     * @param int $pageSize
     * @param int $currentPage
     * @return \Forbesons\AuctionApi\Api\Data\BidSearchResultsInterface
     */
    public function getBidsList(?int $id, ?int $pageSize = 20, ?int $currentPage = 1): BidSearchResultsInterface;
}
