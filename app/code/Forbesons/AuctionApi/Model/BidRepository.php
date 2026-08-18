<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\BidRepositoryInterface;
use Forbesons\AuctionApi\Api\Data\BidSearchResultsInterface;
use Magento\Framework\App\ResourceConnection;

class BidRepository implements BidRepositoryInterface
{
    private BidFactory $bidFactory;
    private BidSearchResultsFactory $searchResultsFactory;
    private ResourceConnection $resourceConnection;

    public function __construct(
        BidFactory $bidFactory,
        BidSearchResultsFactory $searchResultsFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->bidFactory = $bidFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getBidsList(?int $id, ?int $pageSize = 20, ?int $currentPage = 1): BidSearchResultsInterface
    {
        $id = (int)$id;
        $pageSize = max(1, (int)$pageSize);
        $currentPage = max(1, (int)$currentPage);

        $collection = $this->bidFactory->create()
            ->getCollection()
            ->addFieldToFilter('auction_id', ['eq' => $id])
            ->setOrder('created_at', 'DESC')
            ->setPageSize($pageSize)
            ->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $bid) {
            $items[] = $bid;
        }

        $winners = $this->getWinnerMap($id);
        foreach ($items as $bid) {
            $bid->setIsWinner(isset($winners[$this->winnerKey(
                (int)$bid->getData('product_id'),
                (int)$bid->getData('customer_id'),
                (float)$bid->getData('bid_amount')
            )]));
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    private function getWinnerMap(int $auctionId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('manage_bids_detail');
        $select = $connection->select()
            ->from($table, ['product_id', 'customer_id', 'bid_amount'])
            ->where('auction_id = ?', $auctionId)
            ->where('winner_status = ?', 'Winner of Auction');
        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $map[$this->winnerKey(
                (int)$row['product_id'],
                (int)$row['customer_id'],
                (float)$row['bid_amount']
            )] = true;
        }
        return $map;
    }

    private function winnerKey(int $productId, int $customerId, float $amount): string
    {
        return $productId . '|' . $customerId . '|' . $amount;
    }
}