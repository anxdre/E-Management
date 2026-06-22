#!/bin/sh
set -e

# .env is mounted from host — only generate APP_KEY if empty
if ! grep -q "^APP_KEY=[^ ]" .env 2>/dev/null; then
    echo "Generating APP_KEY"
    php -r "
        \$key = 'base64:' . base64_encode(random_bytes(32));
        \$env = preg_replace('/^APP_KEY.*$/m', 'APP_KEY=' . \$key, file_get_contents('.env'), 1, \$count);
        if (\$count === 0) {
            file_put_contents('.env', PHP_EOL . 'APP_KEY=' . \$key, FILE_APPEND);
        } else {
            file_put_contents('.env', \$env);
        }
    "
fi

php artisan storage:link --force 2>/dev/null || true
php artisan migrate --force --seed 2>/dev/null || true
php artisan optimize 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
