<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api\Data;

interface BidInterface
{
    public const BID_ID = 'bid_id';
    public const CUSTOMER_ID = 'customer_id';
    public const CUSTOMER_NAME = 'customer_name';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_NAME = 'product_name';
    public const AUCTION_ID = 'auction_id';
    public const BID_AMOUNT = 'bid_amount';
    public const BID_STATUS = 'bid_status';
    public const WINNER_STATUS = 'winner_status';
    public const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getAuctionId();

    /**
     * @param int $auctionId
     * @return $this
     */
    public function setAuctionId($auctionId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @param string $customerName
     * @return $this
     */
    public function setCustomerName($customerName);

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * @return string
     */
    public function getPlacedAt();

    /**
     * @param string $placedAt
     * @return $this
     */
    public function setPlacedAt($placedAt);

    /**
     * @return bool
     */
    public function getIsWinner();

    /**
     * @param bool $isWinner
     * @return $this
     */
    public function setIsWinner($isWinner);
}
