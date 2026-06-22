# ============================================================
# Stage 1 : Frontend Build (Vite + TypeScript)
# ============================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json pnpm-lock.yaml ./

RUN corepack enable && \
    corepack prepare pnpm@9.15.9 --activate

RUN pnpm install --no-frozen-lockfile

COPY . .

RUN pnpm run build


# ============================================================
# Stage 2 : PHP / Laravel Runtime
# ============================================================
FROM php:8.2-fpm-alpine

# Install system packages
RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    supervisor \
    nginx \
    icu-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev

# Install PHP Extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install \
    gd \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    bcmath \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first (cache optimization)
COPY composer.json composer.lock ./

# Remove platform restriction so composer install works in container
RUN composer config --unset platform.php

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# Copy project files
COPY . .

# Copy Vite build assets
COPY --from=frontend /app/public/build ./public/build

# Run composer scripts now that full source is available
RUN composer dump-autoload

# Laravel permissions
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views && \
    chown -R www-data:www-data storage bootstrap/cache public/storage && \
    chmod -R 775 storage bootstrap/cache

# Storage link
RUN php artisan storage:link --force 2>/dev/null || true

# Optimize Laravel
RUN php artisan optimize

# PHP Opcache
RUN printf "\
opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=4000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=0\n\
" > /usr/local/etc/php/conf.d/opcache.ini

# NGINX configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8181

ENTRYPOINT ["/entrypoint.sh"]
