ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Расширения
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    mbstring \
    zip \
    intl \
    bcmath \
    opcache \
    pcntl \
    redis

# Development
FROM base AS development
RUN install-php-extensions xdebug
RUN mv "$PHP_INI_DIR/php.ini-development"   "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/10-php-dev.ini       $PHP_INI_DIR/conf.d/app.ini
COPY docker/php/conf.d/30-xdebug.ini        $PHP_INI_DIR/conf.d/xdebug.ini
COPY docker/php/php-fpm.d/dev-php-fpm.conf  /usr/local/etc/php-fpm.d/
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# Builder для Production
FROM base AS builder
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist
COPY src/ .
# оптимизация автозагрузки и кэши Laravel
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && php artisan route:cache \
    && php artisan view:cache

# Production
FROM base AS production

RUN mv "$PHP_INI_DIR/php.ini-production"    "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/10-php-prod.ini      $PHP_INI_DIR/conf.d/app.ini
COPY docker/php/conf.d/20-opcache.ini       $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/php-fpm.d/prod-php-fpm.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www/html

# Копируем ТОЛЬКО готовый результат из builder
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]