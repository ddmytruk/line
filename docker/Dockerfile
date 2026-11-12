# Development image: PHP 8.4 CLI with Composer and the MySQL driver for the Laravel adapters and pcov for mutation testing.
FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && pecl install pcov-1.0.12 \
    && docker-php-ext-enable pcov

WORKDIR /app
