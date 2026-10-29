#!/bin/sh
# Boots the demo: fresh SQLite database with seed data, then serves the app on $PORT.
set -e

# Render does not provide a Laravel-format key, so generate one per boot (fine for a demo).
if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
fi

# Use the public URL Render gives the service unless APP_URL is set.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

touch database/database.sqlite
php artisan migrate --force --seed --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
