<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Service;

use Forbesons\Keycloak\Model\Config;
use Forbesons\Keycloak\Model\Oidc\Discovery;
use Magento\Framework\UrlInterface;

class UrlBuilder
{
    private Config $config;
    private Discovery $discovery;
    private UrlInterface $url;

    public function __construct(Config $config, Discovery $discovery, UrlInterface $url)
    {
        $this->config = $config;
        $this->discovery = $discovery;
        $this->url = $url;
    }

    public function getRedirectUri(): string
    {
        return $this->url->getUrl('keycloak/auth/callback', ['_secure' => true]);
    }

    public function getAuthorizationUrl(string $state, string $nonce, string $challenge): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'scope' => $this->config->getScope(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
        return $this->discovery->get('authorization_endpoint') . '?' . http_build_query($params);
    }

    public function getEndSessionUrl(string $idToken): string
    {
        $params = [
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $this->config->getPostLogoutRedirectUri(),
        ];
        $endpoint = $this->discovery->get('end_session_endpoint');
        return $endpoint . '?' . http_build_query($params);
    }
}