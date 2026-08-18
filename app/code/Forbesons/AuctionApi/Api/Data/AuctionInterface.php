<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\AuctionApi\Api\Data;

interface AuctionInterface
{
    public const AUCTION_ID = 'auction_id';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_NAME = 'product_name';
    public const STARTING_PRICE = 'starting_price';
    public const RESERVE_PRICE = 'reserve_price';
    public const START_AUCTION = 'start_auction';
    public const STOP_AUCTION = 'stop_auction';
    public const INCREMENTAL_STATUS = 'incremental_status';
    public const MAIN_STARTING_PRICE = 'main_starting_price';
    public const NEXT_BID_AMT = 'next_bid_amt';
    public const AUCTION_STATUS = 'auction_status';
    public const CREATED_AT = 'created_at';
    public const IMAGE = 'image';

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
     * @return string
     */
    public function getSku();

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * One of ACTIVE|UPCOMING|CLOSED.
     *
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return float
     */
    public function getStartingPrice();

    /**
     * @param float $startingPrice
     * @return $this
     */
    public function setStartingPrice($startingPrice);

    /**
     * Highest bid so far (Milople keeps it in starting_price).
     *
     * @return float
     */
    public function getCurrentPrice();

    /**
     * @param float $currentPrice
     * @return $this
     */
    public function setCurrentPrice($currentPrice);

    /**
     * @return string
     */
    public function getStartAt();

    /**
     * @param string $startAt
     * @return $this
     */
    public function setStartAt($startAt);

    /**
     * @return string
     */
    public function getEndAt();

    /**
     * @param string $endAt
     * @return $this
     */
    public function setEndAt($endAt);

    /**
     * @return float
     */
    public function getMinimumBidIncrement();

    /**
     * @param float $minimumBidIncrement
     * @return $this
     */
    public function setMinimumBidIncrement($minimumBidIncrement);

    /**
     * @return bool
     */
    public function getAllowProxyBidding();

    /**
     * @param bool $allowProxyBidding
     * @return $this
     */
    public function setAllowProxyBidding($allowProxyBidding);

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @return int
     */
    public function getBidsCount();

    /**
     * @param int $bidsCount
     * @return $this
     */
    public function setBidsCount($bidsCount);

    /**
     * Product media gallery image path (e.g. "/f/o/forbes-demo-mtb-001_1.jpg").
     *
     * @return string
     */
    public function getImage();

    /**
     * @param string $image
     * @return $this
     */
    public function setImage($image);
}
