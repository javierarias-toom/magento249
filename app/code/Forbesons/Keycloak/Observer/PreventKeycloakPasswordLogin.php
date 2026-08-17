<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Observer;

use Forbesons\Keycloak\Model\Service\IdentityLinker;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class PreventKeycloakPasswordLogin implements ObserverInterface
{
    private IdentityLinker $identityLinker;

    public function __construct(IdentityLinker $identityLinker)
    {
        $this->identityLinker = $identityLinker;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getData('model');
        if ($customer === null || !$customer->getId()) {
            return;
        }
        $identity = $this->identityLinker->findByCustomerId((int)$customer->getId());
        if ($identity === null) {
            return;
        }
        throw new LocalizedException(
            __('This account uses Forbesons single sign-on. Please use the "Sign in with Forbesons" button instead of a password.')
        );
    }
}