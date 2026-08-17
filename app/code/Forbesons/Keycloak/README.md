# Forbesons_Keycloak

SSO para clientes storefront vía Keycloak / OpenID Connect (Authorization Code + PKCE S256).

## Requisitos

- Magento Open Source 2.4.9
- `firebase/php-jwt` (ya incluido en `composer.lock` de Magento 2.4.9, v7.1.0)
- Keycloak realm `forbesons`, cliente confidencial `magento-store`

## Configuración Keycloak

- Client ID: `magento-store`
- Access Type: `confidential`
- Valid redirect URIs: `https://forbesons.com/keycloak/auth/callback`
- Valid post logout redirect URIs: `https://forbesons.com/`
- Standard flow: ON (Authorization Code)
- PKCE: no obligatorio en Keycloak (el módulo envía `code_challenge`/`code_verifier` de todos modos)
- Scopes: `openid profile email roles`
- Rol requerido: `customer`

## Instalación (no ejecutada aún)

```bash
bin/magento module:enable Forbesons_Keycloak
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuración en Admin

`Stores → Configuration → Customers → Forbesons Keycloak SSO`:

- `Enable SSO` = Yes
- `Issuer URL` = `https://auth.forbesons.com/realms/forbesons`
- `Client ID` = `magento-store`
- `Client Secret` = secret del cliente en Keycloak (se guarda cifrado)
- `Scope` = `openid profile email roles`
- `Required Role` = `customer`
- `Require Verified Email` = Yes
- `Auto Create Customers` = Yes
- `Post Logout Redirect URI` = `https://forbesons.com/`

## Rutas

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/keycloak/auth/login` | Inicia el flujo (redirige a Keycloak) |
| GET | `/keycloak/auth/callback` | Callback OIDC, crea/vinculea cliente y loguea |
| POST | `/keycloak/auth/logout` | Cierra sesión local + end-session en Keycloak |

## Flujo

1. El cliente pulsa "Sign in with Forbesons" en la página de login.
2. El módulo genera `state`, `nonce` y PKCE `code_verifier` (guardados solo en sesión de servidor) y redirige a Keycloak.
3. Keycloak devuelve `code`; el módulo lo canjea por tokens (con `client_secret` + PKCE).
4. El `id_token` se valida (firma JWKS, `iss`, `aud`, `exp`, `nonce`, rol `customer`, `email_verified`).
5. Se localiza al cliente por `sub` en `forbesons_keycloak_identity`; si no existe, se crea con contraseña aleatoria y se vincula.
6. Se inicia sesión del cliente; el `id_token` se guarda en sesión (server-side) para el logout.
7. En logout, se redirige al `end_session_endpoint` de Keycloak con `id_token_hint`.

## Seguridad

- Contraseñas aleatorias: los clientes SSO no pueden usar contraseña local. El observer de `customer_login_attempt` bloquea el login por contraseña si la cuenta está vinculada.
- Sin auto-vincular por email: si existe un cliente con el mismo email sin vínculo, se muestra un error seguro.
- `state` y `nonce` validados con `hash_equals`; el estado se consume una sola vez.
- No se registran tokens ni secretos.
- Logout POST con form key; el plugin del logout nativo de Magento redirige a end-session solo para usuarios SSO.

## Tabla

`forbesons_keycloak_identity`

| Columna | Tipo | Notas |
|---------|------|-------|
| identity_id | int unsigned PK auto | |
| keycloak_sub | varchar(255) UNIQUE | Identidad estable en Keycloak |
| customer_id | int unsigned UNIQUE FK → customer_entity.entity_id | |