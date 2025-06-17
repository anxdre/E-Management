# Stage 1: build
FROM node:20-alpine as build

WORKDIR /app

# Copy file Node & PNPM
COPY package.json pnpm-lock.yaml ./

# Install pnpm & dependencies
RUN npm install -g pnpm && pnpm install

# Copy semua source
COPY . .

# Build vite (pastikan ziggy udah dihandle)
RUN pnpm run build


# Stage 2: final Laravel
FROM php:8.2-fpm-alpine

# Install deps PHP
RUN apk add --no-cache bash zip unzip curl git supervisor libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev oniguruma-dev icu-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring fileinfo bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Buat user non-root (optional)
RUN adduser -D appuser

WORKDIR /var/www

# Copy source dari builder
COPY --from=build /app /var/www

# Set permission
RUN chown -R appuser:appuser /var/www

# Laravel setup
USER appuser

# Install dependencies PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate Telescope assets & migrate (optional)
RUN php artisan telescope:publish \
    && php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000

CMD ["php-fpm"]
