# laravel-solid-starter

A clean starting point for building REST APIs with Laravel 12 — with slim controllers, a real service layer, repository pattern with contracts, typed DTOs, action classes, and custom exceptions, so the foundation stays solid as the project grows.

**Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Laravel Sanctum · Docker

---

## Table of Contents

- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Try It](#try-it)
- [Services](#services)
- [Postman Collection](#postman-collection)
- [API Endpoints](#api-endpoints)
  - [`POST /api/login` — Get Token](#post-apilogin--get-token)
  - [`POST /api/users` — Create User](#post-apiusers--create-user)
  - [`GET /api/users` — List Users](#get-apiusers--list-users)
- [Useful Commands](#useful-commands)
- [Project Structure](#project-structure)
- [Architecture Decisions](#architecture-decisions)
- [About](#about)

---

## Requirements

All you need is [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/) v2. That's it — no local PHP, no local MySQL.

---

## Quick Start

```bash
# Clone and enter the project
git clone https://github.com/anggakersanamunggaran/laravel-solid-starter.git
cd laravel-solid-starter

# Copy the environment file
cp .env.example .env

# Install Composer dependencies
docker compose run --rm app composer install

# Boot all services
docker compose up -d
```

The entrypoint script handles the boring stuff automatically: generates `APP_KEY` if it's missing, waits for MySQL to be ready, and runs migrations. Once it's up, the API is available at **http://localhost:8080**.

If you want some dummy data to play with, run the seeder:

```bash
docker compose exec app php artisan db:seed
```

This creates 1 admin, 2 managers, 10 regular users, and 3 inactive users. All passwords are `password`.

---

## Try It

Once the seeder has run, here's the full flow from zero to authenticated request:

```bash
# 1. Get a token — use any seeded account, all passwords are "password"
curl -s -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
# → copy the "token" value from the response

# 2. List users (paste your token below)
curl -s http://localhost:8080/api/users \
  -H "Authorization: Bearer <your-token>" \
  -H "Accept: application/json"

# 3. Create a user
curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <your-token>" \
  -d '{"name":"Jane Doe","email":"jane@example.com","password":"secret123"}'
```

---

## Services

| Service    | URL / Port            | Notes                                     |
|------------|-----------------------|-------------------------------------------|
| API        | http://localhost:8080 | Laravel app served via Nginx              |
| Mailpit UI | http://localhost:8025 | Catch and inspect outgoing emails locally |
| MySQL      | localhost:33060       | Connect with TablePlus, DBeaver, etc.     |

Port `33060` is intentional — it avoids collisions if you've got a local MySQL instance already running on `3306`.

**DB credentials (local only):**
```
Host:     localhost:33060
Database: laravel_boilerplate
Username: laravel
Password: secret
```

---

## Postman Collection

A ready-to-use Postman collection with all endpoints and an environment pre-configured for `http://localhost:8080`:

[Open in Postman](https://galactic-meteor-165767.postman.co/workspace/My-Workspace~53558f2b-37bf-4818-89d4-cf6a59c6cf97/collection/5356953-b5cc7e84-954d-4f33-a345-997d734be6f8?action=share&creator=5356953&active-environment=5356953-490be36c-c68a-45f7-b918-08bbf347a6f4)

---

## API Endpoints

One thing to keep in mind: always send `Accept: application/json` on every request. Without it, Laravel will return an HTML redirect on validation failures instead of a JSON error — which is confusing and almost certainly not what you want.

### `POST /api/login` — Get Token

All other endpoints require a Bearer token. Seeded accounts:

| Email | Password | Role |
|---|---|---|
| `admin@example.com` | `password` | admin |
| *(any seeded manager/user)* | `password` | manager / user |

```bash
curl -s -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**Response `200 OK`:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

**Response `401 Unauthorized`:**
```json
{
  "message": "Invalid credentials."
}
```

Use the token as a Bearer header on subsequent requests:
```bash
-H "Authorization: Bearer 1|abc123..."
```

---

### `POST /api/users` — Create User

**Requires authentication.**

```bash
curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abc123..." \
  -d '{"email":"john@example.com","password":"secret123","name":"John Doe"}'
```

**Response `201 Created`:**
```json
{
  "id": 1,
  "email": "john@example.com",
  "name": "John Doe",
  "role": "user",
  "created_at": "2024-11-25T12:34:56+00:00"
}
```

**Response `409 Conflict` (duplicate email):**
```json
{
  "message": "The email address [john@example.com] is already registered.",
  "error": "duplicate_email"
}
```

---

### `GET /api/users` — List Users

**Requires authentication.** Get a token first via `POST /api/login`.

```bash
curl -s "http://localhost:8080/api/users?search=john&sortBy=name&page=1" \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

Supported query params:

| Param    | Default       | Description                              |
|----------|--------------|------------------------------------------|
| `search` | —            | Filter by name or email                  |
| `page`   | `1`          | Page number                              |
| `sortBy` | `created_at` | Accepted values: `name`, `email`, `created_at` |

**Response `200 OK`:**
```json
{
  "page": 1,
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "role": "user",
      "created_at": "2024-11-25T12:34:56+00:00",
      "orders_count": 3,
      "can_edit": true
    }
  ]
}
```

`email` is intentionally absent — bulk-listing emails is a PII risk. The `id` remains so the frontend can link to a user's profile page.

---

## Useful Commands

```bash
# View logs from all services
docker compose logs -f

# View only app logs
docker compose logs -f app

# Run any Artisan command
docker compose exec app php artisan <command>

# Run migrations manually
docker compose exec app php artisan migrate

# Wipe the DB and re-run everything from scratch
docker compose exec app php artisan migrate:fresh --seed

# Seed without wiping
docker compose exec app php artisan db:seed

# Run tests
docker compose exec app php artisan test

# Shell into the app container
docker compose exec app bash

# Stop everything
docker compose down

# Stop everything and wipe the database volume
docker compose down -v
```

---

## Project Structure

```
app/
├── Actions/
│   └── CreateUserAction.php            # Write use-case — owns the "create user" flow
├── Contracts/
│   └── Repositories/
│       └── UserRepositoryInterface.php # Abstraction — code depends on this, not the impl
├── DataTransferObjects/
│   └── CreateUserData.php              # PHP 8.2 readonly — typed input, no raw arrays
├── Enums/
│   └── UserRole.php                    # Backed enum: admin | manager | user
├── Exceptions/
│   ├── DuplicateEmailException.php     # Domain error — maps to HTTP 409
│   └── Handler.php                     # Renders DuplicateEmailException → JSON 409
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php          # POST /api/login — returns Sanctum token
│   │   └── UserController.php          # Slim — delegates write to Action, read to Service
│   ├── Requests/Auth/
│   │   └── LoginRequest.php            # Validation for POST /api/login
│   ├── Requests/User/
│   │   ├── CreateUserRequest.php       # Validation for POST /api/users
│   │   └── GetUsersRequest.php         # Validation for GET /api/users
│   └── Resources/
│       ├── UserResource.php            # List view — no email (avoids bulk PII exposure)
│       └── UserCreatedResource.php     # Create response — adds email as registration confirmation
├── Mail/
│   ├── WelcomeUserMail.php             # Queued — sent to new user
│   └── AdminNewUserMail.php            # Queued — sent to admin on new signup
├── Models/User.php
├── Policies/UserPolicy.php             # can_edit logic per role
├── Providers/
│   └── AppServiceProvider.php          # Binds Interface → Implementation (DI container)
├── Repositories/
│   └── UserRepository.php              # All DB queries, implements the interface
└── Services/
    └── UserService.php                 # Read-only: getActiveUsers only
```

---

## Architecture Decisions

The layering is intentional and enforced throughout:

| Layer | Responsibility |
|-------|----------------|
| Controller | Accept request, delegate to Action (write) or Service (read), return a response. Nothing else. |
| FormRequest | All input validation lives here — not in the controller, not in the service. |
| Action | Single write use-case. `CreateUserAction` owns user creation + mail dispatch. |
| Service | Read-only orchestration. `UserService` only handles `getActiveUsers`. |
| Repository | All Eloquent queries. Implements a contract (interface) so it can be swapped. |
| DTO | Typed input carrier. Replaces raw `array $data` passing through layers. |
| Policy | Authorization rules (`can_edit`). |
| Resource | Response shaping — also ensures no sensitive fields leak into API responses. |

---

## About

Built by [Angga Kersana Munggaran](https://github.com/anggakersanamunggaran) — feel free to fork it, break it, and make it your own. If you spot something off or have a suggestion, PRs are welcome.
