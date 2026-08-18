<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api;

use Forbesons\AuctionApi\Api\Data\AuctionInterface;
use Forbesons\AuctionApi\Api\Data\AuctionSearchResultsInterface;

interface AuctionRepositoryInterface
{
    /**
     * Get a paginated list of auctions, optionally filtered by status.
     *
     * @param int $pageSize
     * @param int $currentPage
     * @param string|null $status One of ACTIVE|UPCOMING|CLOSED
     * @return \Forbesons\AuctionApi\Api\Data\AuctionSearchResultsInterface
     */
    public function getList(?int $pageSize = 20, ?int $currentPage = 1, ?string $status = null): AuctionSearchResultsInterface;

    /**
     * Get a single auction by id.
     *
     * @param int $id
     * @return \Forbesons\AuctionApi\Api\Data\AuctionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): AuctionInterface;
}
