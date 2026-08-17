<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Oidc;

use Forbesons\Keycloak\Model\Config;
use Magento\Framework\Exception\LocalizedException;

class IdTokenValidator
{
    private Config $config;
    private Jwks $jwks;

    public function __construct(Config $config, Jwks $jwks)
    {
        $this->config = $config;
        $this->jwks = $jwks;
    }

    /**
     * @return array<string, mixed> validated claims
     */
    public function validate(string $idToken, string $nonce): array
    {
        try {
            $keys = $this->jwks->getKeys();
            $payload = \Firebase\JWT\JWT::decode($idToken, $keys, ['RS256']);
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new LocalizedException(__('The sign-in session has expired. Please try again.'), $e);
        } catch (\Firebase\JWT\BeforeValidException $e) {
            throw new LocalizedException(__('The sign-in session is not yet valid. Please try again.'), $e);
        } catch (\Exception $e) {
            throw new LocalizedException(__('The identity token could not be validated.'), $e);
        }
        $claims = (array)$payload;

        if (empty($claims['iss']) || $claims['iss'] !== rtrim($this->config->getIssuer(), '/')) {
            throw new LocalizedException(__('The identity token issuer does not match.'));
        }
        $audience = $claims['aud'] ?? null;
        if (is_array($audience)) {
            $matched = in_array($this->config->getClientId(), $audience, true);
        } else {
            $matched = $audience === $this->config->getClientId();
        }
        if (!$matched) {
            throw new LocalizedException(__('The identity token audience does not match.'));
        }
        if (empty($claims['nonce']) || !hash_equals($nonce, (string)$claims['nonce'])) {
            throw new LocalizedException(__('The identity token nonce does not match.'));
        }
        if (empty($claims['sub'])) {
            throw new LocalizedException(__('The identity token is missing the subject.'));
        }
        return $claims;
    }
}