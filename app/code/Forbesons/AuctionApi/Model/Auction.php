<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\Data\AuctionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Auction extends AbstractModel implements AuctionInterface
{
    public const STATUS_UPCOMING = 'UPCOMING';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CLOSED = 'CLOSED';

    public const XML_PATH_INCREMENT_ENABLED = 'auction/increment_auction/enable_increment_auction';
    public const XML_PATH_INCREMENT_RANGES = 'auction/increment_auction/ranges';

    public const DEFAULT_BID_INCREMENT = 0.1;

    private ProductRepositoryInterface $productRepository;
    private TimezoneInterface $timezone;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;
    private BidFactory $bidFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        BidFactory $bidFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->bidFactory = $bidFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Forbesons\AuctionApi\Model\ResourceModel\Auction::class);
    }

    public function getId()
    {
        return $this->getData(self::AUCTION_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::AUCTION_ID, $id);
    }

    public function getSku(): string
    {
        if ($this->hasData('sku')) {
            return (string)$this->getData('sku');
        }
        $product = $this->getProduct();
        return $product ? (string)$product->getSku() : '';
    }

    public function setSku($sku)
    {
        return $this->setData('sku', $sku);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::PRODUCT_NAME);
    }

    public function setTitle($title)
    {
        return $this->setData(self::PRODUCT_NAME, $title);
    }

    public function getDescription(): string
    {
        if ($this->hasData('description')) {
            return (string)$this->getData('description');
        }
        $product = $this->getProduct();
        if (!$product) {
            return '';
        }
        return (string)$product->getDescription();
    }

    public function setDescription($description)
    {
        return $this->setData('description', $description);
    }

    public function getStatus(): string
    {
        if ($this->hasData('status')) {
            return (string)$this->getData('status');
        }
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $start = (string)$this->getData(self::START_AUCTION);
        $stop = (string)$this->getData(self::STOP_AUCTION);
        if ($start !== '' && $stop !== '') {
            if ($now < $start) {
                return self::STATUS_UPCOMING;
            }
            if ($now >= $stop) {
                return self::STATUS_CLOSED;
            }
        }
        return self::STATUS_ACTIVE;
    }

    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    public function getStartingPrice(): float
    {
        return (float)$this->getData(self::STARTING_PRICE);
    }

    public function setStartingPrice($startingPrice)
    {
        return $this->setData(self::STARTING_PRICE, $startingPrice);
    }

    public function getCurrentPrice(): float
    {
        return (float)$this->getData(self::STARTING_PRICE);
    }

    public function setCurrentPrice($currentPrice)
    {
        return $this->setData(self::STARTING_PRICE, $currentPrice);
    }

    public function getStartAt(): string
    {
        return (string)$this->getData(self::START_AUCTION);
    }

    public function setStartAt($startAt)
    {
        return $this->setData(self::START_AUCTION, $startAt);
    }

    public function getEndAt(): string
    {
        return (string)$this->getData(self::STOP_AUCTION);
    }

    public function setEndAt($endAt)
    {
        return $this->setData(self::STOP_AUCTION, $endAt);
    }

    public function getMinimumBidIncrement(): float
    {
        $increment = self::DEFAULT_BID_INCREMENT;
        $incrementalEnabled = (int)$this->scopeConfig->getValue(
            self::XML_PATH_INCREMENT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        if ($incrementalEnabled === 1 && (int)$this->getData(self::INCREMENTAL_STATUS) === 1) {
            foreach ($this->getIncrementalRanges() as $item) {
                $from = isset($item['from_qty']) ? (float)$item['from_qty'] : null;
                $to = isset($item['to_qty']) ? (float)$item['to_qty'] : null;
                $price = isset($item['price']) ? (float)$item['price'] : null;
                if ($from !== null && $to !== null && $price !== null) {
                    $current = $this->getCurrentPrice();
                    if ($current >= $from && $current <= $to) {
                        $increment = $price;
                        break;
                    }
                }
            }
        }
        return $increment;
    }

    public function setMinimumBidIncrement($minimumBidIncrement)
    {
        return $this->setData('minimum_bid_increment', $minimumBidIncrement);
    }

    public function getAllowProxyBidding(): bool
    {
        return (bool)$this->getData('allow_proxy_bidding');
    }

    public function setAllowProxyBidding($allowProxyBidding)
    {
        return $this->setData('allow_proxy_bidding', (bool)$allowProxyBidding);
    }

    public function getCurrency(): string
    {
        return (string)$this->storeManager->getStore()->getBaseCurrencyCode();
    }

    public function setCurrency($currency)
    {
        return $this->setData('currency', $currency);
    }

    public function getBidsCount(): int
    {
        if ($this->hasData('bids_count')) {
            return (int)$this->getData('bids_count');
        }
        return (int)$this->bidFactory->create()
            ->getCollection()
            ->addFieldToFilter(self::AUCTION_ID, ['eq' => $this->getId()])
            ->getSize();
    }

    public function setBidsCount($bidsCount)
    {
        return $this->setData('bids_count', $bidsCount);
    }

    private function getProduct(): ?\Magento\Catalog\Api\Data\ProductInterface
    {
        $productId = $this->parseProductId($this->getData(self::PRODUCT_ID));
        if (!$productId) {
            return null;
        }
        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return null;
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

    private function getIncrementalRanges(): array
    {
        $raw = (string)$this->scopeConfig->getValue(
            self::XML_PATH_INCREMENT_RANGES,
            ScopeInterface::SCOPE_STORE
        );
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
