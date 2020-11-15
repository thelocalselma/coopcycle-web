# Produces two images:
# * base - Contains dependencies and a copy of the app; intended for development.
# * latest - Contains built production resources; suitable for deployment.

FROM php:7.4-fpm-alpine3.12 as base

# Versions / environments
ENV APP_ENV prod
ENV APCU_VERSION 5.1.18
ENV PHPREDIS_VERSION 5.1.1


# Install packages, Composer and PHP extensions
RUN apk update

RUN apk add --no-cache \
        git \
        icu \
        zlib

RUN set -xe \
    && apk add --no-cache \
        icu-dev \
        postgresql-dev \
        zlib-dev \
    && apk add --no-cache \
        libpng \
        libjpeg-turbo \
        freetype \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && apk add --no-cache \
        unzip \
        libzip-dev \
    && apk add --no-cache \
        jpegoptim \
        optipng \
        pngquant \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure pdo_pgsql \
    && docker-php-ext-configure gd \
        --enable-gd \
        --with-freetype=/usr/include/ \
        --with-jpeg=/usr/include/ \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
        zip \
        bcmath \
        gd \
        pcntl \
    && pecl install \
        apcu-${APCU_VERSION} \
        redis-${PHPREDIS_VERSION} \
    && docker-php-ext-enable --ini-name 20-apcu.ini apcu \
    && docker-php-ext-enable --ini-name 05-opcache.ini opcache \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/install-composer.sh /usr/local/bin/docker-app-install-composer

RUN chmod +x /usr/local/bin/docker-app-install-composer
COPY docker/php/enable-xdebug.sh /usr/local/bin/enable-xdebug

RUN set -xe \
    && apk add --no-cache --virtual .fetch-deps \
        openssl \
    && sh /usr/local/bin/docker-app-install-composer \
    && mv composer.phar /usr/local/bin/composer \
    && apk del .fetch-deps

COPY docker/php/start.sh /usr/local/bin/docker-app-start
COPY docker/php/enable-xdebug.sh /usr/local/bin/enable-xdebug
RUN chmod +x /usr/local/bin/docker-app-start
RUN chmod +x /usr/local/bin/enable-xdebug

# Add healthcheck for PHP-FPM
# https://github.com/docker-library/php/issues/366
# https://github.com/renatomefi/php-fpm-healthcheck

RUN apk add --no-cache fcgi

RUN set -xe && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

RUN wget -O /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

HEALTHCHECK \
  CMD php-fpm-healthcheck || exit 1

# Install a static version of the app; for development, map the repository over /server.
RUN mkdir /server
RUN chown -R www-data /server
USER www-data

RUN mkdir /server/var
RUN mkdir /server/var/cache
RUN mkdir /server/var/logs
WORKDIR /server

# First copy everything needed for composer.install then run it
# (Running install first makes the build up to here cacheable)
COPY composer.json /server
COPY composer.lock /server
RUN composer install --no-scripts --no-plugins --prefer-dist --no-plugins --no-progress --no-suggest --no-dev --no-autoloader

# Then copy the app
COPY app /server/app/
COPY bin /server/bin/
COPY src /server/src/
COPY --chown=www-data web /server/web/
COPY templates /server/templates/

ENTRYPOINT ["docker-app-start"]
CMD ["php-fpm"]

# Create a fresh node container just to run encore build
FROM node:12-alpine3.12 as encore_build
RUN apk add git
RUN mkdir /server
COPY assets /server/assets/
COPY web /server/web/
COPY src /server/src/
COPY js /server/js/
COPY webpack.config.js package.json package-lock.json postcss.config.js babel.config.js /server/
RUN cd /server \
    && npm install \
    && node_modules/.bin/encore production

# Merge the base image with the built assets.
FROM base
COPY --chown=www-data --from=encore_build /server/web/build/ /server/web/build/
