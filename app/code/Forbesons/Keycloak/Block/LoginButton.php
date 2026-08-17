<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Block;

use Magento\Framework\View\Element\Template;

class LoginButton extends Template
{
    public function getLoginUrl(): string
    {
        return $this->getUrl('keycloak/auth/login');
    }

    public function isEnabled(): bool
    {
        return (bool)$this->_scopeConfig->getValue('customer/forbesons_keycloak/enabled');
    }
}