<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\BidRepositoryInterface;
use Forbesons\AuctionApi\Api\Data\BidInterface;
use Forbesons\AuctionApi\Api\Data\BidSearchResultsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class BidRepository implements BidRepositoryInterface
{
    private BidFactory $bidFactory;
    private BidSearchResultsFactory $searchResultsFactory;
    private ResourceConnection $resourceConnection;
    private AuctionFactory $auctionFactory;

    public function __construct(
        BidFactory $bidFactory,
        BidSearchResultsFactory $searchResultsFactory,
        ResourceConnection $resourceConnection,
        AuctionFactory $auctionFactory
    ) {
        $this->bidFactory = $bidFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceConnection = $resourceConnection;
        $this->auctionFactory = $auctionFactory;
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

    public function placeBid(?int $id, string $customerSub, string $customerName, float $amount): BidInterface
    {
        $auctionId = (int)$id;
        $connection = $this->resourceConnection->getConnection();

        $auction = $this->auctionFactory->create()->load($auctionId);
        if (!$auction->getId()) {
            throw new NoSuchEntityException(__('Auction with id "%1" does not exist.', $auctionId));
        }
        if ($auction->getStatus() !== \Forbesons\AuctionApi\Model\Auction::STATUS_ACTIVE) {
            throw new LocalizedException(__('Auction "%1" is not active.', $auctionId));
        }

        $identityTable = $connection->getTableName('forbesons_keycloak_identity');
        $identity = $connection->fetchRow(
            $connection->select()
                ->from($identityTable, ['customer_id'])
                ->where('keycloak_sub = ?', $customerSub)
                ->limit(1)
        );
        if (!$identity) {
            throw new LocalizedException(__('No Magento customer linked to the given Keycloak identity.'));
        }
        $customerId = (int)$identity['customer_id'];

        $productId = $this->parseProductId($auction->getData(\Forbesons\AuctionApi\Api\Data\AuctionInterface::PRODUCT_ID));
        $productName = (string)$auction->getData(\Forbesons\AuctionApi\Api\Data\AuctionInterface::PRODUCT_NAME);

        $bidsTable = $connection->getTableName('manage_bids');
        $connection->insert($bidsTable, [
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'product_id' => $productId,
            'product_name' => $productName,
            'bid_amount' => $amount,
            'auction_id' => $auctionId,
            'bid_status' => 'Processing',
            'winner_status' => 'Processing',
            'mail_chk' => 0,
            'expire_link' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $bidId = (int)$connection->lastInsertId();

        $detailTable = $connection->getTableName('manage_bids_detail');
        $existing = $connection->fetchRow(
            $connection->select()
                ->from($detailTable, ['bid_id'])
                ->where('product_id = ?', $productId)
                ->where('customer_id = ?', $customerId)
                ->limit(1)
        );
        if ($existing) {
            $connection->update(
                $detailTable,
                ['bid_amount' => $amount, 'created_at' => date('Y-m-d H:i:s')],
                ['bid_id = ?' => (int)$existing['bid_id']]
            );
        } else {
            $connection->insert($detailTable, [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'product_id' => $productId,
                'product_name' => $productName,
                'bid_amount' => $amount,
                'auction_id' => $auctionId,
                'bid_status' => 'Processing',
                'winner_status' => 'Processing',
                'mail_chk' => 0,
                'expire_link' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $auctionTable = $connection->getTableName('manage_auction');
        $auction->setData(\Forbesons\AuctionApi\Api\Data\AuctionInterface::STARTING_PRICE, $amount);
        $connection->update(
            $auctionTable,
            ['starting_price' => $amount, 'next_bid_amt' => $amount + $auction->getMinimumBidIncrement()],
            ['auction_id = ?' => $auctionId]
        );

        $bid = $this->bidFactory->create();
        $bid->setAuctionId($auctionId)
            ->setCustomerId($customerId)
            ->setCustomerName($customerName)
            ->setAmount($amount)
            ->setPlacedAt(date('Y-m-d H:i:s'))
            ->setIsWinner(false);
        $bid->setData(\Forbesons\AuctionApi\Api\Data\BidInterface::PRODUCT_ID, $productId);
        $bid->setData(\Forbesons\AuctionApi\Api\Data\BidInterface::BID_ID, $bidId);

        return $bid;
    }

    private function parseProductId($raw): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }
        $raw = trim((string)$raw);
        if (preg_match('/(\d+)\s*$/', $raw, $matches)) {
            return (int)$matches[1];
        }
        return (int)$raw;
    }
}