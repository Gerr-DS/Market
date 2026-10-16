# Surplus Market API

Backend for the second-hand goods and surplus food marketplace, built on Laravel 12.
Sellers provide product details, prices, and food expiry dates manually. The application does not use AI for product listings.

## Included

- Single user account model with Sanctum bearer tokens and user/admin roles.
- PostgreSQL schema for users, addresses, categories, products, orders, payments,
  wallet ledger, withdrawals, chats, and reports.
- Product soft deletes; hidden/deleted products are excluded from public listings.
- Manual expiry-date entry for food listings, rejection at 30 days or less before expiry,
  and seller approval.
- Escrow payment webhook signature validation, shipment submission, disputes, 72-hour release,
  and ledger-backed seller wallet credits.
- Daily hard deletion of food listings and their images at H-7, plus hourly escrow release scheduler.

## Setup

Requires PHP 8.3+, Composer, PostgreSQL 16, and Redis.

1. Copy `.env.example` to `.env` and set database, Redis, and webhook secrets.
2. Run `composer install`, `php artisan key:generate`, `php artisan migrate --seed`.
3. Start the API with `php artisan serve`; start a queue worker with `php artisan queue:work`.
4. Keep `php artisan schedule:work` running so H-7 food deletion and scheduled tasks execute.

Composer dependencies are recorded in `composer.lock` and passed the security audit during
installation.

API base path: `/api/v1`. Main endpoints: `POST /auth/register`, `POST /auth/login`,
`GET /products`, `POST /products/analyze`, `POST /orders/checkout`,
`POST /orders/{order}/ship`, `POST /orders/{order}/confirm`, and
`POST /orders/{order}/reports`. Authenticated wallet, chat, seller product, and admin
report/withdrawal endpoints are also registered in `routes/api.php`.

Courier delivery updates still require a logistics webhook integration and secret. Sellers enter
their prices manually; DPrice and the seller-entered reference can lower the price base before the
selected condition discount is applied to used goods.

Shipping quotes use RajaOngkir's free Starter API. Add its API key to `RAJAONGKIR_API_KEY` in `.env`.
The Starter package currently advertises 100 shipping-rate API hits per day. Location search results
are cached for one day. Sellers choose their origin area and package weight; buyers choose a
destination area and courier service at checkout. The server uses the saved quote when creating the
order so the browser cannot change the shipping price. The map uses Leaflet and OpenStreetMap as a
visual location aid; RajaOngkir calculates by selected area and weight. For local phone testing,
open the app using the computer's LAN IP instead of `127.0.0.1`.
