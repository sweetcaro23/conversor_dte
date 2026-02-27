FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
  nginx bash curl git unzip \
  icu-dev oniguruma-dev libzip-dev \
  libpng-dev libjpeg-turbo-dev freetype-dev \
  libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) \
     intl mbstring zip opcache pdo pdo_mysql bcmath gd xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY nginx.conf /etc/nginx/http.d/default.conf

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
