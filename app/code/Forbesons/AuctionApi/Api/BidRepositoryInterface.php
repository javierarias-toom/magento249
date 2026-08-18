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

    /**
     * Record a bid placed through the Node bid engine.
     *
     * Replicates Milople's own bid placement (manage_bids insert, manage_bids_detail
     * upsert and manage_auction.starting_price / next_bid_amt update) so Magento
     * becomes the source of truth for the bid history and current price.
     *
     * @param int $id Auction id
     * @param string $customerSub Keycloak subject of the bidding customer
     * @param string $customerName Display name of the bidding customer
     * @param float $amount Bid amount
     * @return \Forbesons\AuctionApi\Api\Data\BidInterface
     */
    public function placeBid(?int $id, string $customerSub, string $customerName, float $amount): BidInterface;
}
