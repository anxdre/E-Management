#!/bin/sh
set -e

if [ ! -f .env ]; then
    echo "Copying .env.example → .env"
    cp .env.example .env
fi

#if ! grep -q "^APP_KEY=" .env || [ "$(grep "^APP_KEY=" .env | cut -d= -f2)" = "" ]; then
#    echo "Generating APP_KEY"
#    php artisan key:generate --force
#fi

echo "Creating storage link"
php artisan storage:link --force 2>/dev/null || true

echo "Optimizing Laravel"
php artisan optimize

exec /usr/bin/supervisord -c /etc/supervisord.conf
