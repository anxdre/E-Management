# ============================================================
# Stage 1 : Frontend Build (Vite)
# ============================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json pnpm-lock.yaml ./

RUN corepack enable && \
    pnpm install --frozen-lockfile

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

# Laravel permissions
RUN mkdir -p storage/logs && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Optimize Laravel
RUN php artisan config:clear || true && \
    php artisan route:clear || true && \
    php artisan view:clear || true

# PHP Opcache
RUN printf "\
opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=4000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=0\n\
" > /usr/local/etc/php/conf.d/opcache.ini

# NGINX
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 8181

ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
