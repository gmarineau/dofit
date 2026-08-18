#!/bin/sh
set -e

echo " [-] Caching Laravel config, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

role=${CONTAINER_ROLE:-app}

case "$role" in
    app)
        echo " [-] Container running as app"
        exec frankenphp run --config /etc/caddy/Caddyfile
        ;;
    queue)
        echo " [-] Container running as queue worker"
        exec php artisan queue:work --tries=3
        ;;
    scheduler)
        echo " [-] Migrating the database"
        php artisan app:install

        echo " [-] Container running as scheduler"
        exec php artisan schedule:work
        ;;
    *)
        echo " [X] Could not match the container role \"$role\""
        exit 1
        ;;
esac
