# Stage 1: Build frontend assets
FROM node:20-alpine AS node-builder
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
    bcmath \
    intl \
    zip \
    gd \
    opcache

WORKDIR /app

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .
# Copy compiled frontend assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Install production PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

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
