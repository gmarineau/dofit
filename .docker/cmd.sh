#!/bin/sh
set -e

echo " [-] Caching Laravel config, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

role=${CONTAINER_ROLE:-app}

case "$role" in
    app)
        # The symlink is gitignored, so it is absent from the image: without
        # this, everything on the `public` media disk answers 404.
        echo " [-] Linking storage/app/public into the document root"
        php artisan storage:link --force

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
