<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Oidc;

use Forbesons\Keycloak\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class Discovery
{
    private Curl $curl;
    private Json $json;
    private Config $config;
    private ?array $metadata = null;

    public function __construct(Curl $curl, Json $json, Config $config)
    {
        $this->curl = $curl;
        $this->json = $json;
        $this->config = $config;
    }

    public function get(string $key): string
    {
        $metadata = $this->load();
        if (!isset($metadata[$key])) {
            throw new LocalizedException(__('Keycloak discovery document is missing "%1".', $key));
        }
        return (string)$metadata[$key];
    }

    private function load(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }
        $issuer = rtrim($this->config->getIssuer(), '/');
        $url = $issuer . '/.well-known/openid-configuration';
        try {
            $this->curl->get($url);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to reach the Keycloak discovery endpoint.'), $e);
        }
        $status = $this->curl->getStatus();
        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('Keycloak discovery endpoint returned HTTP %1.', $status));
        }
        try {
            $body = $this->curl->getBody();
            $data = $this->json->unserialize($body);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Invalid response from the Keycloak discovery endpoint.'), $e);
        }
        if (!is_array($data)) {
            throw new LocalizedException(__('Invalid response from the Keycloak discovery endpoint.'));
        }
        return $this->metadata = $data;
    }
}