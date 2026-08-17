<?php
/**
 * Copyright © Forbesons. All rights reserved.
 */

namespace Forbesons\Keycloak\Model\Oidc;

use Magento\Framework\Exception\LocalizedException;

class ClaimsExtractor
{
    /**
     * @param array<string, mixed> $claims
     * @return array{sub: string, email: string, email_verified: bool, given_name: string, family_name: string, roles: string[]}
     */
    public function extract(array $claims): array
    {
        if (empty($claims['sub'])) {
            throw new LocalizedException(__('The identity token is missing the subject.'));
        }
        $roles = [];
        if (!empty($claims['realm_access']) && is_array($claims['realm_access']) && !empty($claims['realm_access']['roles'])) {
            $roles = array_values(array_map('strval', $claims['realm_access']['roles']));
        }
        return [
            'sub' => (string)$claims['sub'],
            'email' => isset($claims['email']) ? strtolower((string)$claims['email']) : '',
            'email_verified' => !empty($claims['email_verified']),
            'given_name' => isset($claims['given_name']) ? (string)$claims['given_name'] : '',
            'family_name' => isset($claims['family_name']) ? (string)$claims['family_name'] : '',
            'roles' => $roles,
        ];
    }
}