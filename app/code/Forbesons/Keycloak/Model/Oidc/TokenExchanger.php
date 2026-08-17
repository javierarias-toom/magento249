<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Oidc;

use Forbesons\Keycloak\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

class TokenExchanger
{
    private Curl $curl;
    private Json $json;
    private Config $config;
    private Discovery $discovery;

    public function __construct(Curl $curl, Json $json, Config $config, Discovery $discovery)
    {
        $this->curl = $curl;
        $this->json = $json;
        $this->config = $config;
        $this->discovery = $discovery;
    }

    /**
     * @return array{id_token: string, access_token: string, refresh_token?: string}
     */
    public function exchange(string $code, string $redirectUri, string $codeVerifier): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'code_verifier' => $codeVerifier,
        ];
        $url = $this->discovery->get('token_endpoint');
        try {
            $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            $this->curl->post($url, $params);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to reach the Keycloak token endpoint.'), $e);
        }
        $status = $this->curl->getStatus();
        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('Keycloak token endpoint returned HTTP %1.', $status));
        }
        try {
            $body = $this->curl->getBody();
            $data = $this->json->unserialize($body);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Invalid response from the Keycloak token endpoint.'), $e);
        }
        if (!is_array($data) || empty($data['id_token']) || empty($data['access_token'])) {
            throw new LocalizedException(__('Keycloak did not return a valid token response.'));
        }
        return [
            'id_token' => (string)$data['id_token'],
            'access_token' => (string)$data['access_token'],
            'refresh_token' => isset($data['refresh_token']) ? (string)$data['refresh_token'] : null,
        ];
    }
}