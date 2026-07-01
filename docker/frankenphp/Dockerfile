FROM dunglas/frankenphp:1-php8.4-alpine

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=:80 \
    COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions pdo_pgsql intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --no-progress

COPY . .

RUN composer dump-autoload --optimize --no-interaction \
    && php bin/console cache:clear --no-warmup \
    && php bin/console cache:warmup \
    && mkdir -p var \
    && chown -R www-data:www-data var

COPY docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
