<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\AuctionRepositoryInterface;
use Forbesons\AuctionApi\Api\Data\AuctionInterface;
use Forbesons\AuctionApi\Api\Data\AuctionSearchResultsInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class AuctionRepository implements AuctionRepositoryInterface
{
    private AuctionFactory $auctionFactory;
    private TimezoneInterface $timezone;
    private AuctionSearchResultsFactory $searchResultsFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private ResourceConnection $resourceConnection;

    public function __construct(
        AuctionFactory $auctionFactory,
        TimezoneInterface $timezone,
        AuctionSearchResultsFactory $searchResultsFactory,
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->auctionFactory = $auctionFactory;
        $this->timezone = $timezone;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getById(int $id): AuctionInterface
    {
        $auction = $this->auctionFactory->create();
        $auction->load($id);
        if (!$auction->getId()) {
            throw new NoSuchEntityException(__('Auction with id "%1" does not exist.', $id));
        }
        return $auction;
    }

    public function getList(?int $pageSize = 20, ?int $currentPage = 1, ?string $status = null): AuctionSearchResultsInterface
    {
        $pageSize = max(1, (int)$pageSize);
        $currentPage = max(1, (int)$currentPage);

        $collection = $this->auctionFactory->create()->getCollection();
        $status = $status !== null ? strtoupper(trim((string)$status)) : '';
        if ($status !== '') {
            $now = $this->timezone->date()->format('Y-m-d H:i:s');
            switch ($status) {
                case Auction::STATUS_UPCOMING:
                    $collection->addFieldToFilter('start_auction', ['gt' => $now]);
                    break;
                case Auction::STATUS_ACTIVE:
                    $collection->addFieldToFilter('start_auction', ['lteq' => $now]);
                    $collection->addFieldToFilter('stop_auction', ['gt' => $now]);
                    break;
                case Auction::STATUS_CLOSED:
                    $collection->addFieldToFilter('stop_auction', ['lteq' => $now]);
                    break;
            }
        }

        $collection->setPageSize($pageSize)->setCurPage($currentPage);

        $items = [];
        foreach ($collection as $auction) {
            $items[] = $auction;
        }

        $this->preloadProducts($items);
        $this->preloadBidsCount($items);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @param Auction[] $items
     */
    private function preloadProducts(array $items): void
    {
        $ids = [];
        foreach ($items as $auction) {
            $productId = $this->parseProductId($auction->getData('product_id'));
            if ($productId > 0) {
                $ids[$productId] = $productId;
            }
        }
        if (!$ids) {
            return;
        }
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['sku', 'description'])
            ->addFieldToFilter('entity_id', ['in' => array_values($ids)]);
        $products = [];
        foreach ($collection as $product) {
            $products[(int)$product->getId()] = $product;
        }
        foreach ($items as $auction) {
            $productId = $this->parseProductId($auction->getData('product_id'));
            if (isset($products[$productId])) {
                $auction->setData('sku', (string)$products[$productId]->getSku());
                $auction->setData('description', (string)$products[$productId]->getDescription());
            } else {
                $auction->setData('sku', '');
                $auction->setData('description', '');
            }
        }
    }

    /**
     * @param Auction[] $items
     */
    private function preloadBidsCount(array $items): void
    {
        $ids = [];
        foreach ($items as $auction) {
            $auctionId = (int)$auction->getId();
            if ($auctionId > 0) {
                $ids[$auctionId] = $auctionId;
            }
        }
        if (!$ids) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('manage_bids');
        $select = $connection->select()
            ->from($table, ['auction_id', 'cnt' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('auction_id IN (?)', array_values($ids))
            ->group('auction_id');
        $counts = $connection->fetchPairs($select);
        foreach ($items as $auction) {
            $auction->setData('bids_count', (int)($counts[(int)$auction->getId()] ?? 0));
        }
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