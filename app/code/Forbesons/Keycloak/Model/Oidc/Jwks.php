<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Oidc;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class Jwks
{
    private Curl $curl;
    private Json $json;
    private Discovery $discovery;
    private ?array $keys = null;

    public function __construct(Curl $curl, Json $json, Discovery $discovery)
    {
        $this->curl = $curl;
        $this->json = $json;
        $this->discovery = $discovery;
    }

    /**
     * @return array<string, \Firebase\JWT\Key> keys indexed by kid
     */
    public function getKeys(): array
    {
        if ($this->keys !== null) {
            return $this->keys;
        }
        $url = $this->discovery->get('jwks_uri');
        try {
            $this->curl->get($url);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to reach the Keycloak JWKS endpoint.'), $e);
        }
        $status = $this->curl->getStatus();
        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('Keycloak JWKS endpoint returned HTTP %1.', $status));
        }
        try {
            $body = $this->curl->getBody();
            $data = $this->json->unserialize($body);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Invalid response from the Keycloak JWKS endpoint.'), $e);
        }
        if (!is_array($data) || !isset($data['keys']) || !is_array($data['keys'])) {
            throw new LocalizedException(__('Invalid response from the Keycloak JWKS endpoint.'));
        }
        $keys = [];
        foreach ($data['keys'] as $jwk) {
            $key = \Firebase\JWT\JWK::parseKey($jwk);
            $kid = $jwk['kid'] ?? null;
            if ($kid !== null) {
                $keys[$kid] = $key;
            }
        }
        return $this->keys = $keys;
    }
}