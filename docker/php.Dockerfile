FROM composer:2.8@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c AS dependencies
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
FROM php:8.4.26-fpm-alpine@sha256:78cd8de9970a9cd6ff4d98860a94eb5bf37dd2d5776b4785562dbfad01c29d5a
WORKDIR /app/backend
COPY docker/php.ini-production /usr/local/etc/php/conf.d/zz-production.ini
COPY --from=dependencies /app/vendor ./vendor
COPY backend ./
COPY data /app/data
