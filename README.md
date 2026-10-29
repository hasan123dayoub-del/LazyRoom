# 🛒 LazyRoom — Multi-Vendor Ordering API

LazyRoom is a RESTful backend for a multi-vendor marketplace where **customers** order food and grocery products from **suppliers**, payments are settled through an in-app **wallet**, and **admins** manage accounts and balances.

[![tests](https://github.com/hasan123dayoub-del/LazyRoom/actions/workflows/tests.yml/badge.svg)](https://github.com/hasan123dayoub-del/LazyRoom/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Sanctum](https://img.shields.io/badge/Auth-Sanctum-FF2D20)
![MySQL](https://img.shields.io/badge/DB-MySQL-4479A1?logo=mysql&logoColor=white)

## 🌐 Live demo

**API base URL:** (added after the first Render deploy)

- **Interactive API docs (try every endpoint in the browser):** `/docs/api`, generated from the code with [Scramble](https://scramble.dedoc.co). The login and order requests are pre-filled with working demo values.
- Demo accounts (password `password` for all): customer `customer@lazyroom.com` (wallet 100 USD), supplier `supplier@lazyroom.com` (products 1–4), admin `admin@lazyroom.com`. A second supplier, `fresh@lazyroom.com`, owns products 5–8.
- Try the order flow: log in as the customer → `POST /api/orders` → log in as the supplier → `POST /api/orders/{id}/accept` → log in as the customer → `POST /api/orders/{id}/complete` and check `GET /api/balance`.
- The mobile app was built by a teammate in Flutter; this repository is the backend.
- Hosted on Render's free tier with SQLite: the first request after a period of inactivity can take about a minute, and the demo data resets on every restart. SQLite has no row locks, so the concurrency results below were measured on MySQL.

---

## ✨ Features

### 👤 Customer
- Browse all products or filter them by category.
- Place multi-item orders with automatic stock checks.
- Cancel pending orders (stock is restored).
- Complete accepted orders — the amount moves from the customer's wallet to the supplier's.
- View order history and wallet balance.

### 🏪 Supplier
- Full CRUD on their own products (with Form Request validation).
- See incoming orders containing their products, with per-supplier totals.
- Accept or reject pending orders (rejection returns items to stock).

### 🛡 Admin
- List, create and delete accounts.
- Ban / unban users.
- Deposit to customer wallets and withdraw from supplier wallets.

---

## 🧠 Technical Highlights

- **Role-based access control** — a custom `CheckRole` middleware (`role:customer`, `role:supplier`, `role:admin`) on top of Sanctum token auth.
- **Database transactions** — placing, cancelling, rejecting and completing orders run inside transactions, so stock and balances never end up half-updated.
- **Pessimistic locking** — completing an order locks the customer row (`lockForUpdate()`) before checking and moving the balance, preventing double-spending under concurrent requests.
- **Order state machine** — `pending → accept → complete`, or `pending → reject / cancelled`, with checks at each step.
- **Eager loading** of order items and products to avoid N+1 queries.

---

### Measured: concurrent wallet payments

Parallel `complete` requests against MySQL (InnoDB), each from a separate PHP process, all released at the same moment ([`benchmarks/concurrency`](benchmarks/concurrency)). Order price 60.

| Scenario | Before (customer row locked only) | After (order row locked and re-checked) |
|---|---|---|
| Same order, 20 parallel requests, balance 1000 | Charged **16 times** (960 taken) | Charged **once**, 19 requests get `409` (3 runs) |
| 10 different orders, balance covers 1 | 1 charged, balance never negative | Same |
| 5 orders x 10 requests (50 total), balance covers 2 | Not run | **2 charged**, balance ends at 0, never negative |

The fix locks the order row inside the transaction and re-checks that it is still `accept` before touching the wallet, so duplicate requests for one order wait and then fail instead of charging again.

## 🛠 Tech Stack

| Layer | Technology |
| :--- | :--- |
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Authentication | Laravel Sanctum |
| Database | MySQL |
| API Testing | Postman |

## 🗄 Data Model

```text
[ User ] (role: customer | supplier | admin, balance, is_banned)
   │
   ├── supplier 1 ──── * [ Product ] * ──── 1 [ Category ]
   │                         │
   └── customer 1 ──── * [ Order ] 1 ──── * [ OrderItem ] * ──── 1 [ Product ]
```

---

## 🚀 Getting Started

```bash
# 1. Clone the repository
git clone https://github.com/hasan123dayoub-del/LazyRoom.git
cd LazyRoom

# 2. Install dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lazyroom
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 4. Create the tables and seed categories & users
php artisan migrate --seed

# 5. Run the server
php artisan serve
```

The API is available at **http://127.0.0.1:8000/api**.

---

## 📡 API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :---: |
| `POST` | `/api/register` | Create an account | — |
| `POST` | `/api/login` | Log in and receive a token | — |
| `POST` | `/api/logout` | Revoke the current token | ✅ |
| `DELETE` | `/api/delete-account` | Delete the account | ✅ |
| `GET` | `/api/balance` | Show wallet balance | ✅ |

### Customer (`role:customer`)

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/allProducts` | List all products |
| `GET` | `/api/product/category/{id}` | Products by category |
| `POST` | `/api/orders` | Place an order |
| `GET` | `/api/my-orders` | My orders |
| `POST` | `/api/orders/{id}/cancel` | Cancel a pending order |
| `POST` | `/api/orders/{id}/complete` | Complete an accepted order (pay) |

### Supplier (`role:supplier`)

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/supplier/products` | My products |
| `POST` | `/api/storeProduct` | Add a product |
| `PUT` | `/api/updateProduct/{id}` | Update a product |
| `DELETE` | `/api/deleteProduct/{id}` | Delete a product |
| `GET` | `/api/supplier/orders` | Orders containing my products |
| `POST` | `/api/orders/{id}/accept` | Accept an order |
| `POST` | `/api/orders/{id}/reject` | Reject an order |

### Admin (`role:admin`)

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/accounts` | List accounts |
| `POST` | `/api/accounts` | Create an account |
| `DELETE` | `/api/accounts/{id}` | Delete an account |
| `POST` | `/api/ban/{id}` | Ban a user |
| `POST` | `/api/unban/{id}` | Unban a user |
| `POST` | `/api/deposit` | Deposit to a customer's wallet |
| `POST` | `/api/withdraw` | Withdraw from a supplier's wallet |

---

## 🧪 Postman Collection

📥 **[Download the Postman collection](Lazy%20Room.postman_collection.json)** and import it to try every endpoint.

---

## 👥 Team

Built as a team project — backend API with Laravel.

## 🙋 My contribution (Hasan Dayoub)

The project was assigned to a team, and I developed the entire backend API myself, working directly on the `main` branch. That covers the customer, supplier and admin roles, the order state machine, the wallet with payments protected by database transactions and row locking (pessimistic locking) against double charges, and the admin module.
