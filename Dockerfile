FROM node:22-alpine AS assets
WORKDIR /src
COPY package*.json ./
RUN npm ci
COPY resources resources
COPY public public
COPY vite.config.js tsconfig.json ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache icu-libs libpq libzip oniguruma && apk add --no-cache --virtual .build icu-dev postgresql-dev libzip-dev $PHPIZE_DEPS && docker-php-ext-install bcmath intl opcache pcntl pdo_pgsql zip && pecl install redis && docker-php-ext-enable redis && apk del .build
WORKDIR /var/www
COPY --from=vendor /src/vendor vendor
COPY . .
COPY --from=assets /src/public/build public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/sis.ini
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm","-F"]

FROM nginx:1.29-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
COPY --from=assets /src/public/build /var/www/public/build
