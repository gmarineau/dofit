# syntax=docker/dockerfile:1

# The application is copied in already built: the build workflow installs the
# production Composer dependencies and compiles the Vite assets beforehand.

ARG PHP_VERSION=8.4

FROM dunglas/frankenphp:1-php${PHP_VERSION}-alpine

LABEL org.opencontainers.image.title="DoFit" \
      org.opencontainers.image.description="Track your trainings, activities and body metrics." \
      org.opencontainers.image.source="https://github.com/gmarineau/dofit" \
      org.opencontainers.image.licenses="MIT"

ENV SERVER_NAME=:80 \
    PHP_MEMORY_LIMIT=256M \
    PHP_UPLOAD_MAX_FILESIZE=32M \
    PHP_POST_MAX_SIZE=32M

# curl is the binary used by the healthcheck below, not the PHP extension,
# which the official image already bundles.
RUN apk add --no-cache curl

# Everything else Laravel needs (ctype, dom, fileinfo, filter, hash, iconv,
# json, libxml, mbstring, openssl, pcre, session, tokenizer, pdo_sqlite) is
# built into the official PHP image already.
RUN install-php-extensions \
        opcache \
        pcntl \
        pdo_mysql \
        redis

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i "s|;*memory_limit =.*|memory_limit = ${PHP_MEMORY_LIMIT}|i" "$PHP_INI_DIR/php.ini" \
    && sed -i "s|;*upload_max_filesize =.*|upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}|i" "$PHP_INI_DIR/php.ini" \
    && sed -i "s|;*post_max_size =.*|post_max_size = ${PHP_POST_MAX_SIZE}|i" "$PHP_INI_DIR/php.ini"

COPY .docker/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY .docker/Caddyfile /etc/caddy/Caddyfile
COPY .docker/cmd.sh /usr/local/bin/cmd.sh
RUN chmod +x /usr/local/bin/cmd.sh

WORKDIR /app

COPY --chown=www-data:www-data . /app

RUN rm -Rf /app/.docker \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -sf http://localhost/up || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/cmd.sh"]
