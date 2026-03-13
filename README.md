# laravel-solid-starter

A clean starting point for building REST APIs with Laravel 12. The main goal here was to enforce proper separation of concerns from day one — slim controllers, a real service layer, repository pattern, the works — so you're not refactoring architectural mistakes six months in.

**Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Laravel Sanctum · Docker

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

This creates 1 admin (`admin@example.com`), 2 managers, 10 regular users, and 3 inactive users. All passwords are `password`.

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

## API Endpoints

One thing to keep in mind: always send `Accept: application/json` on every request. Without it, Laravel will return an HTML redirect on validation failures instead of a JSON error — which is confusing and almost certainly not what you want.

### `POST /api/users` — Create User

```bash
curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"john@example.com","password":"secret123","name":"John Doe"}'
```

**Response `201 Created`:**
```json
{
  "id": 1,
  "email": "john@example.com",
  "name": "John Doe",
  "created_at": "2024-11-25T12:34:56+00:00"
}
```

---

### `GET /api/users` — List Users

```bash
curl -s "http://localhost:8080/api/users?search=john&sortBy=name&page=1"
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
      "email": "john@example.com",
      "name": "John Doe",
      "role": "user",
      "created_at": "2024-11-25T12:34:56+00:00",
      "orders_count": 3,
      "can_edit": true
    }
  ]
}
```

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
├── Enums/UserRole.php              # Backed enum: admin | manager | user
├── Http/
│   ├── Controllers/Api/
│   │   └── UserController.php      # Slim controller — delegates to UserService
│   ├── Requests/User/
│   │   ├── CreateUserRequest.php   # Validation for POST /api/users
│   │   └── GetUsersRequest.php     # Validation for GET /api/users
│   └── Resources/
│       └── UserResource.php        # API response transformation
├── Mail/
│   ├── WelcomeUserMail.php         # Queued — sent to new user
│   └── AdminNewUserMail.php        # Queued — sent to admin on new signup
├── Models/User.php
├── Policies/UserPolicy.php         # can_edit logic per role
├── Repositories/UserRepository.php # All DB queries live here
└── Services/UserService.php        # Business logic layer
```

---

## Architecture Decisions

The layering is intentional and enforced throughout:

| Layer | Responsibility |
|-------|----------------|
| Controller | Accept request, delegate to the service, return a response. Nothing else. |
| FormRequest | All input validation lives here — not in the controller, not in the service. |
| Service | Business logic and orchestration, including mail dispatch. |
| Repository | All Eloquent queries. The service never touches Eloquent directly. |
| Policy | Authorization rules (`can_edit`). |
| Resource | Response shaping — also ensures no sensitive fields leak into API responses. |

---

## About

Built by [Angga Kersana Munggaran](https://github.com/anggakersanamunggaran) — feel free to fork it, break it, and make it your own. If you spot something off or have a suggestion, PRs are welcome.
