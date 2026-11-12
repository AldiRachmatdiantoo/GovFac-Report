# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: vendor — dependensi produksi (tanpa paket dev)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

# Disalin lebih dulu agar layer ini tetap ter-cache selama composer.* tidak berubah
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-scripts --no-autoloader \
    --prefer-dist --no-interaction --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------------------------------------------------------------------------
# Stage 2: base — PHP-FPM + ekstensi yang dipakai dev maupun produksi
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS base
WORKDIR /var/www/html

RUN apk add --no-cache icu-libs libpq libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS linux-headers icu-dev postgresql-dev libzip-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip gd opcache pcntl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# ---------------------------------------------------------------------------
# Stage 3: dev — Composer + Xdebug; kode di-mount sebagai volume oleh Compose
# ---------------------------------------------------------------------------
FROM base AS dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-deps
COPY docker/php/dev.ini /usr/local/etc/php/conf.d/zz-app.ini

CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 4: production — image ramping, kode + vendor disalin, user non-root
# ---------------------------------------------------------------------------
FROM base AS production

COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor

RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

# config:cache / route:cache dijalankan saat container start (butuh .env runtime);
# ini bagian dari PBI deploy yang ditunda.
CMD ["php-fpm"]
