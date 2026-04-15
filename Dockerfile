FROM composer:2 AS composer_deps

WORKDIR /app

COPY app/composer.json app/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

FROM node:20-alpine AS frontend_build

WORKDIR /app

COPY app/package.json app/yarn.lock ./
RUN yarn install --frozen-lockfile

COPY app/assets ./assets
COPY app/webpack.config.js ./webpack.config.js
COPY app/tsconfig.json ./tsconfig.json

RUN yarn build

FROM php:8.3-fpm-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo \
        pdo_pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/app

COPY app/ /var/www/html/app/
COPY --from=composer_deps /app/vendor /var/www/html/app/vendor
COPY --from=frontend_build /app/public/build /var/www/html/app/public/build

RUN mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data /var/www/html/app/var

ENV APP_ENV=prod
ENV APP_DEBUG=0

CMD ["php-fpm"]