<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Service;

use Forbesons\Keycloak\Model\Identity;
use Forbesons\Keycloak\Model\IdentityFactory;
use Forbesons\Keycloak\Model\ResourceModel\Identity as IdentityResource;
use Magento\Framework\Exception\LocalizedException;

class IdentityLinker
{
    private IdentityFactory $identityFactory;
    private IdentityResource $identityResource;

    public function __construct(IdentityFactory $identityFactory, IdentityResource $identityResource)
    {
        $this->identityFactory = $identityFactory;
        $this->identityResource = $identityResource;
    }

    public function findBySub(string $sub): ?Identity
    {
        $identity = $this->identityFactory->create();
        $this->identityResource->load($identity, $sub, 'keycloak_sub');
        return $identity->getId() ? $identity : null;
    }

    public function findByCustomerId(int $customerId): ?Identity
    {
        $identity = $this->identityFactory->create();
        $this->identityResource->load($identity, $customerId, 'customer_id');
        return $identity->getId() ? $identity : null;
    }

    public function link(int $customerId, string $sub): Identity
    {
        /** @var Identity $identity */
        $identity = $this->identityFactory->create();
        $identity->setData('customer_id', $customerId);
        $identity->setData('keycloak_sub', $sub);
        try {
            $this->identityResource->save($identity);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to link the Keycloak identity to the customer account.'), $e);
        }
        return $identity;
    }
}