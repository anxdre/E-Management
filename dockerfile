FROM php:8.2-fpm-alpine

# Install system deps
RUN apk add --no-cache nginx bash git curl zip unzip supervisor nodejs npm icu-dev zlib-dev libzip-dev oniguruma-dev libpng-dev jpeg-dev freetype-dev

# PHP extensions
RUN docker-php-ext-configure zip
RUN docker-php-ext-install pdo pdo_mysql mbstring zip intl bcmath exif opcache

# GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create app user
RUN adduser -D -u 1000 www && chown -R www:www /var/www

WORKDIR /var/www

# Copy project
COPY . .

# Permissions
RUN chmod -R 775 storage bootstrap/cache && chown -R www:www .

# Build frontend
RUN npm install && npm run build

# Install PHP deps
RUN composer install --optimize-autoloader --no-dev

# Opcache for performance
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=4000\n\
opcache.validate_timestamps=0\n" > /usr/local/etc/php/conf.d/opcache.ini

# NGINX config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
