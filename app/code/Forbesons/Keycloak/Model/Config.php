<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    public const XML_PATH_ENABLED = 'customer/forbesons_keycloak/enabled';
    public const XML_PATH_ISSUER = 'customer/forbesons_keycloak/issuer';
    public const XML_PATH_CLIENT_ID = 'customer/forbesons_keycloak/client_id';
    public const XML_PATH_CLIENT_SECRET = 'customer/forbesons_keycloak/client_secret';
    public const XML_PATH_SCOPE = 'customer/forbesons_keycloak/scope';
    public const XML_PATH_REQUIRED_ROLE = 'customer/forbesons_keycloak/required_role';
    public const XML_PATH_REQUIRE_EMAIL_VERIFIED = 'customer/forbesons_keycloak/require_email_verified';
    public const XML_PATH_CREATE_CUSTOMER = 'customer/forbesons_keycloak/create_customer';
    public const XML_PATH_POST_LOGOUT_REDIRECT_URI = 'customer/forbesons_keycloak/post_logout_redirect_uri';

    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;

    public function __construct(ScopeConfigInterface $scopeConfig, EncryptorInterface $encryptor)
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLED);
    }

    public function getIssuer(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_ISSUER);
    }

    public function getClientId(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CLIENT_ID);
    }

    public function getClientSecret(): string
    {
        return (string)$this->encryptor->decrypt((string)$this->scopeConfig->getValue(self::XML_PATH_CLIENT_SECRET));
    }

    public function getScope(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_SCOPE);
    }

    public function getRequiredRole(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_REQUIRED_ROLE);
    }

    public function isEmailVerificationRequired(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_REQUIRE_EMAIL_VERIFIED);
    }

    public function canCreateCustomer(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_CREATE_CUSTOMER);
    }

    public function getPostLogoutRedirectUri(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_POST_LOGOUT_REDIRECT_URI);
    }
}