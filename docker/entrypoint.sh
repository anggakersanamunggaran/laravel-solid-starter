#!/usr/bin/env bash
#
# Docker entrypoint for the PHP-FPM application container.
#
# Execution order:
#   1. Wait for the database to accept connections.
#   2. Copy .env if it is missing (first boot convenience).
#   3. Generate APP_KEY if not set.
#   4. Run pending migrations (safe — uses --force in production).
#   5. Hand off to php-fpm.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
log() {
    echo "[entrypoint] $*"
}

wait_for_db() {
    local host="${DB_HOST:-db}"
    local port="${DB_PORT:-3306}"
    local retries=30

    log "Waiting for database at ${host}:${port} ..."
    until php -r "new PDO('mysql:host=${host};port=${port}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        retries=$((retries - 1))
        if [ "$retries" -le 0 ]; then
            log "ERROR: Could not connect to the database after 30 attempts. Aborting."
            exit 1
        fi
        log "  database not ready, retrying in 2 s ... (${retries} attempts left)"
        sleep 2
    done
    log "Database is ready."
}

# ---------------------------------------------------------------------------
# Bootstrap
# ---------------------------------------------------------------------------

# Ensure .env exists (copy from example on first boot inside the container)
if [ ! -f /var/www/html/.env ]; then
    log ".env not found — copying from .env.example"
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate app key if not already set
if grep -q "^APP_KEY=$" /var/www/html/.env || grep -q "^APP_KEY=\"\"$" /var/www/html/.env; then
    log "Generating application key ..."
    php /var/www/html/artisan key:generate --ansi
fi

# Wait for the database service
wait_for_db

# Run migrations — --force is required when APP_ENV=production
log "Running database migrations ..."
php /var/www/html/artisan migrate --force --no-interaction

# Clear and cache config/routes for production; skip in local dev
if [ "${APP_ENV:-local}" != "local" ]; then
    log "Caching configuration, routes and views ..."
    php /var/www/html/artisan config:cache
    php /var/www/html/artisan route:cache
    php /var/www/html/artisan view:cache
fi

log "Starting php-fpm ..."
exec php-fpm
