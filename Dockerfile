ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine

LABEL authors="Jacob Tyler, Letchworth, Baldock & Ashwell Scouts"
LABEL org.opencontainers.image.title="District Team"
LABEL org.opencontainers.image.description="CakePHP application for managing the Letchworth, Baldock and Ashwell Scouts district team."
LABEL org.opencontainers.image.source="https://github.com/LBDistrictScouts/DistrictTeam"

ARG user=www-data
ARG group=www-data

ENV TAR_OPTIONS="--no-same-owner"

RUN apk add --no-cache bash icu-dev libpq-dev postgresql17-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pgsql \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql pgsql

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /home/$user/.composer \
    && chown -R $user:$group /home/$user

USER $user
WORKDIR /var/www/html

COPY --chown=$user:$group composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist

COPY --chown=$user:$group . .

RUN test ! -f config/app_local.php \
    && composer run-script post-install-cmd --no-interaction \
    && rm -f config/app_local.php \
    && mkdir -p logs \
    && ln -sf /dev/stdout logs/debug.log \
    && ln -sf /dev/stderr logs/error.log \
    && test ! -f config/app_local.php

CMD ["php-fpm", "-F"]
