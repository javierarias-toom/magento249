# Forbesons_AuctionApi — Endpoints REST de subastas

Módulo Magento 2 que expone las subastas de **Milople_Auction** a través de REST Web API, porque
Milople no registra rutas `webapi.xml` (devuelve `Request does not match any route`).

El módulo es **solo lectura**: lee las tablas que crea Milople (`manage_auction`,
`manage_bids`, `manage_bids_detail`) y no modifica ningún dato. Las pujas siguen entrando por el
frontend de Magento/Milople.

Ruta: `app/code/Forbesons/AuctionApi`. Depende de `Milople_Auction` (ver `etc/module.xml`).

---

## Endpoints

Base: `https://<magento>/rest/V1`

### 1. Listado de subastas

```
GET /rest/V1/auctions?pageSize=20&currentPage=1&status=ACTIVE
```

Parámetros (todos opcionales):

| Parámetro    | Tipo   | Valores                              | Default |
|--------------|--------|--------------------------------------|---------|
| `pageSize`   | int    | 1..N                                 | 20      |
| `currentPage`| int    | 1..N                                 | 1       |
| `status`     | string | `ACTIVE`, `UPCOMING`, `CLOSED` (sin el filtro devuelve todas) | — |

Respuesta:

```json
{
  "items": [ { "id": 1, "sku": "P001", "title": "...", "description": "...",
               "status": "ACTIVE", "starting_price": 100, "current_price": 120,
               "start_at": "2026-08-10 10:00:00", "end_at": "2026-08-20 10:00:00",
               "minimum_bid_increment": 10, "allow_proxy_bidding": false,
               "currency": "USD", "bids_count": 3 } ],
  "total_count": 1
}
```

### 2. Detalle de subasta

```
GET /rest/V1/auctions/{id}
```

Devuelve un único objeto con la misma forma que un elemento de `items` del listado.
Si no existe: `404 {"message": "Auction with id \"N\" does not exist."}`.

### 3. Pujas de una subasta

```
GET /rest/V1/auctions/{id}/bids?pageSize=20&currentPage=1
```

Respuesta (ordenadas por `created_at` descendente):

```json
{
  "items": [ { "id": 5, "auction_id": 1, "customer_id": 42, "customer_name": "Ana Perez",
               "amount": 120, "placed_at": "2026-08-15 14:22:10", "is_winner": true } ],
  "total_count": 3
}
```

---

## Mapeo de campos (API → tabla Milople)

### Auction

| Campo API            | Origen                                                                                     |
|----------------------|--------------------------------------------------------------------------------------------|
| `id`                 | `manage_auction.auction_id`                                                                 |
| `sku`                | SKU del producto de catálogo cuyo `entity_id` = `manage_auction.product_id` (caché en memoria al listar) |
| `title`              | `manage_auction.product_name`                                                               |
| `description`        | Atributo `description` del producto (supuesto, ver abajo)                                   |
| `status`             | Derivado de fechas: `now < start_auction` → `UPCOMING`; `now >= stop_auction` → `CLOSED`; resto → `ACTIVE` |
| `starting_price`     | `manage_auction.starting_price`                                                             |
| `current_price`      | `manage_auction.starting_price` (Milople lo actualiza a la última puja ganadora)            |
| `start_at`           | `manage_auction.start_auction`                                                              |
| `end_at`             | `manage_auction.stop_auction`                                                               |
| `minimum_bid_increment` | Regla de incremento de Milople (ver abajo)                                              |
| `allow_proxy_bidding`| `false` (Milople no tiene pujas proxy; supuesto, ver abajo)                                 |
| `currency`           | Moneda base de la tienda (`store.base_currency_code`)                                       |
| `bids_count`         | `COUNT(*)` de `manage_bids` por `auction_id` (conteo en una sola query al listar)           |

### Bid

| Campo API      | Origen                                                        |
|----------------|---------------------------------------------------------------|
| `id`           | `manage_bids.bid_id`                                          |
| `auction_id`   | `manage_bids.auction_id`                                      |
| `customer_id`  | `manage_bids.customer_id`                                     |
| `customer_name`| `manage_bids.customer_name`                                   |
| `amount`       | `manage_bids.bid_amount`                                      |
| `placed_at`    | `manage_bids.created_at`                                      |
| `is_winner`    | `true` si existe fila en `manage_bids_detail` con el mismo `(auction_id, product_id, customer_id, bid_amount)` y `winner_status = 'Winner of Auction'` |

---

## Reglas de Milople reproducidas

1. **Estado de la subasta (tiempo):** El cron `Cron/Winnermail.php` de Milople usa
   `current_date >= stop_auction` para cerrar la subasta y `bid_amt >= reserve_price` para
   declarar ganador. El módulo replica esa comparación textual con la fecha-hora local de la
   tienda (`TimezoneInterface::date()` en formato `Y-m-d H:i:s`), igual que
   `Helper\Data::getStoreDateTime()`.
2. **Filtro por estado en el listado:** se traduce a rangos de fechas en SQL para no cargar
   todo: `UPCOMING → start_auction > now`, `ACTIVE → start_auction <= now AND stop_auction > now`,
   `CLOSED → stop_auction <= now`.
3. **Incremento mínimo de puja:** `Controller/Bid/Index.php` calcula la siguiente puja como
   `starting_price + incremento`, donde el incremento es:
   - Si la config global `auction/increment_auction/enable_increment_auction = 1` **y** la
     subasta tiene `incremental_status = 1`: se busca en `auction/increment_auction/ranges`
     (JSON array de `{from_qty, to_qty, price}`) el primer rango que contenga el precio actual
     (`from_qty <= current <= to_qty`) y el incremento es ese `price`.
   - En cualquier otro caso: `0.1`.
4. **Precio actual = última puja:** Milople hace `UPDATE manage_auction SET starting_price = bid_amount`
   en cada puja aceptada, por eso `current_price` y `starting_price` son la misma columna.
5. **Ganador:** el cron marca en `manage_bids_detail` `bid_status='Complete'`,
   `winner_status='Winner of Auction'` a la puja ganadora y `winner_status='Lost'` al resto.
   `is_winner` se resuelve contra esa marca.

---

## Decisiones de seguridad

- **Solo lectura y solo GET.** No se exponen métodos de creación/modificación de subastas ni de
  pujas por esta API.
- **Acceso `anonymous`** en las tres rutas (los datos de subastas son públicos en el frontend de
  Milople). Las pujas incluyen `customer_name`, que también es visible públicamente en la página
  de producto de Milople. Si se quisiera restringir, cambiar `resource ref="anonymous"` por un
  recurso ACL propio en `etc/webapi.xml`.
- **No hay `db_schema.xml` propio**: el módulo reutiliza las tablas declaradas por
  `Milople_Auction` y no crea ni altera tablas.

---

## Supuestos (no derivables de la referencia de Milople)

- `description`: Milople no guarda descripción de subasta; se toma del atributo `description`
  del producto asociado. Si no existe producto → cadena vacía.
- `allow_proxy_bidding`: Milople no implementa puja proxy; se devuelve `false` de forma fija.
- `currency`: no se almacena por subasta; se usa la moneda base de la tienda.
- `is_winner`: depende de que el cron `Milople_Auction\Cron\Winnermail` haya ejecutado y marcado
  el ganador. Si el envío de email a ganador está deshabilitado en config, Milople no marca el
  ganador y el listado de pujas devolverá `is_winner=false` para todas.
- `bid_id` de `manage_bids_detail` es un autoincremento independiente del `bid_id` de
  `manage_bids`; por eso la coincidencia del ganador se hace por
  `(auction_id, product_id, customer_id, bid_amount)` y no por id.
- En `manage_auction` no existe columna `reauction` como marca visible para esta API; se ignora.
- `no_of_days` y `tmp_product_id` son datos operativos de Milople que no se exponen.

---

## Pendiente de verificar en el VPS

1. `php bin/magento setup:upgrade` y `php bin/magento cache:flush` para que Magento registre el
   módulo, genere las factorías (`*Factory`) y compile el `webapi.xml`.
2. Comprobar que `Milople_Auction` está instalado y que las tablas `manage_auction`,
   `manage_bids`, `manage_bids_detail` existen con datos.
3. Probar las tres rutas con un token de integración o acceso anónimo:
   `curl https://<magento>/rest/V1/auctions`.
4. Confirmar el formato real de `auction/increment_auction/ranges` en `core_config_data`
   (debe ser un JSON array de `{from_qty, to_qty, price}`).
5. Confirmar que `manage_auction.starting_price` se actualiza a la última puja (regla 4) en la
   instalación real.
6. Confirmar la zona horaria de la tienda vs. los valores almacenados en `start_auction` /
   `stop_auction` para que la comparación textual de fechas dé el estado correcto.
7. `php bin/magento setup:di:compile` y `php bin/magento cache:flush` tras desplegar.