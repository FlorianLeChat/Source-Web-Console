# syntax=docker/dockerfile:1

FROM php:8.2-apache AS base

RUN apt update && \
    apt install -y cron tzdata && \
    apt clean && \
    rm -rf /var/lib/apt/lists/*

ADD --chmod=0755 \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    /usr/local/bin/

WORKDIR /var/www/html

FROM node:20-slim AS assets

WORKDIR /usr/src/app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

FROM base AS development

COPY --chown=www-data:www-data . .

RUN install-php-extensions zip pdo_mysql redis intl xdebug bcmath excimer @composer && \
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    echo "xdebug.mode=develop,debug" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.client_port=9003" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.log=/tmp/xdebug.log" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.start_with_request=yes" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.client_host=host.docker.internal" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "* * * * * /usr/local/bin/php /var/www/html/bin/console app:tasks-executor > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data && \
    echo "0 * * * * /usr/local/bin/php /var/www/html/bin/console app:statistics-collector > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data

COPY --from=ghcr.io/symfony-cli/symfony-cli:latest /usr/local/bin/symfony /usr/local/bin/symfony

FROM base AS production

RUN a2enmod rewrite cache cache_disk && \
    a2dissite 000-default.conf && \
    sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf && \
    install-php-extensions \
        zip \
        pdo_mysql \
        redis \
        opcache \
        intl \
        bcmath \
        excimer \
        @composer

COPY --chown=www-data:www-data composer.json composer.lock symfony.lock* ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY docker/configuration/vhost-default.conf /etc/apache2/sites-available/
COPY --chown=www-data:www-data docker/scripts/production/entrypoint.sh /var/www/html/entrypoint.sh
COPY --from=assets --chown=www-data:www-data /usr/src/app/public/build ./public/build
COPY --chown=www-data:www-data . .

RUN composer dump-env prod && \
    a2ensite vhost-default.conf && \
    echo "* * * * * /usr/local/bin/php /var/www/html/bin/console app:tasks-executor > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data && \
    echo "0 * * * * /usr/local/bin/php /var/www/html/bin/console app:statistics-collector > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    chmod +x /var/www/html/entrypoint.sh && \
    sed -i \
        -e "s/;realpath_cache_ttl = 120/realpath_cache_ttl = 600/g" \
        -e "s/expose_php = On/expose_php = Off/g" \
        -e "s/session.use_strict_mode = 0/session.use_strict_mode = 1/g" \
        -e "s/;session.cookie_secure =/session.cookie_secure = 1/g" \
        -e "s/session.name = PHPSESSID/session.name = __SECURE-PHPSESSID/g" \
        -e "s/session.cookie_httponly =/session.cookie_httponly = 1/g" \
        -e "s/session.cookie_samesite =/session.cookie_samesite = Strict/g" \
        -e "s/;opcache.revalidate_freq=2/opcache.revalidate_freq=60/g" \
        -e "s/;opcache.validate_timestamps=1/opcache.validate_timestamps=0/g" \
        -e "s/;opcache.memory_consumption=128/opcache.memory_consumption=256/g" \
        -e "s/;opcache.interned_strings_buffer=8/opcache.interned_strings_buffer=16/g" \
        -e "s/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=20000/g" \
        "$PHP_INI_DIR/php.ini"
