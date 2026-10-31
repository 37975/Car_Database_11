#!/bin/sh
# Container entrypoint for Render (or any Docker host).
# Creates the SQLite database on first boot, migrates, seeds once, then serves the app.
set -e
cd /app
echo "[start] user=$(id -u) php=$(php -r 'echo PHP_VERSION;') port=${PORT:-8080}"

# Render gives a random string from `generateValue`; Laravel needs a 32-byte "base64:" key.
# Derive a stable key from whatever was provided so restarts keep sessions valid.
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is empty; generating a temporary key (sessions reset on every restart)."
    APP_KEY=$(php artisan key:generate --show --no-interaction)
elif [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    APP_KEY=$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY"), true));')
fi
export APP_KEY

# Use Render's public URL when APP_URL was not set explicitly.
if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi

DB_FILE="${DB_DATABASE:-/app/database/database.sqlite}"
export DB_DATABASE="$DB_FILE"
FRESH=0
if [ ! -f "$DB_FILE" ]; then
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    FRESH=1
fi

echo "[start] migrating $DB_DATABASE"
php artisan migrate --force --no-interaction
if [ "$FRESH" = "1" ]; then
    echo "New database: seeding tiers, admin, brands, categories and cars."
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link --no-interaction >/dev/null 2>&1 || true
php artisan optimize --no-interaction

echo "[start] launching FrankenPHP on :${PORT:-8080}"
exec frankenphp php-server --root /app/public --listen ":${PORT:-8080}"
