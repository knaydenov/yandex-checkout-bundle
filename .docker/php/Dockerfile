FROM php:7.2.4-fpm-alpine3.7

RUN set -ex \
    && apk --no-cache add libmemcached-libs git openssh \
    && apk --no-cache --virtual .deps add zlib-dev libmemcached-dev \
    && docker-php-source extract \
    && git clone --branch php7 https://github.com/php-memcached-dev/php-memcached /usr/src/php/ext/memcached/ \
    && docker-php-ext-install -j$(nproc) zip memcached \
    && apk del .deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_CACHE_DIR=/var/www/html/.composer
