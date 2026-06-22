#!/bin/sh
set -e

if [ ! -f .env ]; then
    echo "Copying .env.example → .env"
    cp .env.example .env
fi

# Generate APP_KEY directly in .env file (bypass artisan boot to avoid Telescope issue)
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    echo "Generating APP_KEY"
    php -r "
        \$key = 'base64:' . base64_encode(random_bytes(32));
        \$env = preg_replace('/^APP_KEY.*\$', 'APP_KEY=' . \$key, file_get_contents('.env'), 1, \$count);
        if (\$count === 0) {
            file_put_contents('.env', PHP_EOL . 'APP_KEY=' . \$key, FILE_APPEND);
        } else {
            file_put_contents('.env', \$env);
        }
    "
fi

echo "Creating storage link"
php artisan storage:link --force 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
