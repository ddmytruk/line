# Development image: PHP 8.4 CLI with Composer and the MySQL driver for the Laravel adapters.
FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql

WORKDIR /app
