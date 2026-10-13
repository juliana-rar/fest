#!/bin/sh
# Container entrypoint for the "web" service.
# vendor/ lives in a named volume (fast Linux filesystem instead of the slow
# Windows bind mount), so it starts empty: install dependencies on first run.
set -e
cd /var/www

if [ ! -f vendor/autoload.php ]; then
    echo "vendor/ is empty: running composer install…"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

exec "$@"
