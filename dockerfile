# Stage 1: Build frontend with pnpm
FROM node:20-alpine as frontend

WORKDIR /app

COPY package.json pnpm-lock.yaml ./

RUN npm install -g pnpm && pnpm install

COPY . .

RUN pnpm run build


# Stage 2: Laravel with PHP-FPM + NGINX
FROM php:8.2-fpm-alpine

# Install PHP extensions & system tools
RUN apk add --no-cache \
    bash git curl zip unzip supervisor nginx \
    icu-dev zlib-dev libzip-dev oniguruma-dev \
    libpng-dev jpeg-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring zip intl bcmath opcache


# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project
WORKDIR /var/www

COPY . .

# Copy built frontend from Stage 1
COPY --from=frontend /app/public /var/www/public

# Set permissions
RUN chmod -R 775 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Install Telescope secara eksplisit
# RUN composer require laravel/telescope --no-interaction

# Production PHP deps
RUN composer install --optimize-autoloader --no-dev

# Set PHP Opcache
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=4000\n\
opcache.validate_timestamps=0\n" > /usr/local/etc/php/conf.d/opcache.ini

# NGINX config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

#take ownership
WORKDIR /var/www
RUN chown -R www-data:www-data .
RUN chmod -R 755 .

EXPOSE 8181

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
