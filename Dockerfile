# Stage 1: Build frontend assets
FROM node:22-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Production PHP Application with FrankenPHP
FROM dunglas/frankenphp:1-php8.2-alpine

# Install PHP extensions required by Laravel
RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    intl \
    zip \
    gd \
    opcache

RUN apk add --no-cache --virtual .builddeps libcap && \
    setcap -r /usr/local/bin/frankenphp && \
    apk del .builddeps

# php.ini production + opcache không kiểm tra lại file (code trong image không đổi sau khi build)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'memory_limit=256M'; \
        echo 'date.timezone=Asia/Ho_Chi_Minh'; \
    } > "$PHP_INI_DIR/conf.d/zz-production.ini"

WORKDIR /app

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cài thư viện trước khi copy code: sửa code không phải cài lại vendor (tận dụng cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Copy application files
COPY . .
# Copy compiled frontend assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

RUN rm -f config/scribe.php && \
    composer dump-autoload --no-dev --optimize

# Setup storage and cache directories
RUN mkdir -p \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/database && \
    touch /app/database/database.sqlite && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

# Setup entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV PORT=8000
EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
