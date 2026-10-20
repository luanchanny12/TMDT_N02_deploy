FROM php:8.2-fpm-bookworm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_pgsql mbstring zip gd intl bcmath exif opcache sockets

# mysqldump / mysql client cho lệnh `php artisan db:backup` và phục hồi DB
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www
