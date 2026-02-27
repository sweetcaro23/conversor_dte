# Imagen con PHP-FPM
FROM php:8.2-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache nginx bash curl git unzip icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install intl mbstring zip opcache pdo pdo_mysql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos el 
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Config Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

# Permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Script de arranque
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]