<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Model;

use Forbesons\AuctionApi\Api\Data\BidInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Bid extends AbstractModel implements BidInterface
{
    public function __construct(
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Forbesons\AuctionApi\Model\ResourceModel\Bid::class);
    }

    public function getId()
    {
        return $this->getData(self::BID_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::BID_ID, $id);
    }

    public function getAuctionId(): int
    {
        return (int)$this->getData(self::AUCTION_ID);
    }

    public function setAuctionId($auctionId)
    {
        return $this->setData(self::AUCTION_ID, $auctionId);
    }

    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getCustomerName(): string
    {
        return (string)$this->getData(self::CUSTOMER_NAME);
    }

    public function setCustomerName($customerName)
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::BID_AMOUNT);
    }

    public function setAmount($amount)
    {
        return $this->setData(self::BID_AMOUNT, $amount);
    }

    public function getPlacedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setPlacedAt($placedAt)
    {
        return $this->setData(self::CREATED_AT, $placedAt);
    }

    public function getIsWinner(): bool
    {
        return (bool)$this->getData('is_winner');
    }

    public function setIsWinner($isWinner)
    {
        return $this->setData('is_winner', (bool)$isWinner);
    }
}
