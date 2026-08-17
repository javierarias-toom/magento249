<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Session;

use Magento\Customer\Model\Session;

class AuthState
{
    private const KEY = 'forbesons_keycloak_auth';
    private const KEY_ID_TOKEN = 'forbesons_keycloak_id_token';

    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return array{state: string, nonce: string, verifier: string, challenge: string}
     */
    public function start(): array
    {
        $state = $this->random();
        $nonce = $this->random();
        $verifier = $this->random(48);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->session->setData(self::KEY, [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
        ]);
        return [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'challenge' => $challenge,
        ];
    }

    public function consume(string $state): ?array
    {
        $data = $this->session->getData(self::KEY);
        $this->session->unsetData(self::KEY);
        if (!is_array($data) || empty($data['state']) || !hash_equals((string)$data['state'], $state)) {
            return null;
        }
        return $data;
    }

    public function saveIdToken(string $idToken): void
    {
        $this->session->setData(self::KEY_ID_TOKEN, $idToken);
    }

    public function getIdToken(): ?string
    {
        $token = $this->session->getData(self::KEY_ID_TOKEN);
        return is_string($token) && $token !== '' ? $token : null;
    }

    public function clear(): void
    {
        $this->session->unsetData(self::KEY);
        $this->session->unsetData(self::KEY_ID_TOKEN);
    }

    private function random(int $bytes = 24): string
    {
        return bin2hex(random_bytes($bytes));
    }
}