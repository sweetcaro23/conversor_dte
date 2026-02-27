#!/usr/bin/env bash
set -e

# Si no existe APP_KEY, la app puede fallar
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Levanta PHP-FPM en background
php-fpm -D

# Levanta Nginx en foreground
nginx -g "daemon off;"