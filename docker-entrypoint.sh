#!/bin/sh
set -e

# Ensure SQLite file exists and is writable
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
fi
chown -R www-data:www-data /app/database /app/storage /app/bootstrap/cache

# Run database migrations and seed demo data
php artisan migrate --force
php artisan db:seed --force || true

# Optimize configuration and route caching
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start FrankenPHP server listening on PORT
exec frankenphp php-server --listen ":${PORT:-8000}" --root /app/public
