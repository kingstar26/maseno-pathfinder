FROM composer:2 AS composer

FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" mysqli mbstring curl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# fileinfo, json, and openssl are built into the official PHP image.
RUN php -m | grep -E '^(curl|fileinfo|json|mbstring|mysqli|openssl)$' >/dev/null

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR ${APACHE_DOCUMENT_ROOT}

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

COPY backend/ ./

CMD ["sh", "-c", "port=\"${PORT:-80}\"; sed -ri \"s/^Listen [0-9]+/Listen ${port}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${port}>/\" /etc/apache2/sites-available/000-default.conf; mkdir -p /run/mugts; cp /etc/secrets/ca.pem /run/mugts/ca.pem; chown www-data:www-data /run/mugts/ca.pem; chmod 0400 /run/mugts/ca.pem; export DB_SSL_CA_PATH=/run/mugts/ca.pem; exec apache2-foreground"]
