#!/usr/bin/env bash
set -e

# Crear carpetas necesarias de Laravel
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/bootstrap/cache

# Permisos (Render/Docker)
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# Limpieza / cache (no debe botar el deploy si algo falta)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm -D
nginx -g "daemon off;"
