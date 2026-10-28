# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.2 AS base

RUN apt-get update && \
    apt-get install -y --no-install-recommends cron tzdata && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM node:22-slim AS assets

WORKDIR /usr/src/app

COPY package*.json ./

RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY . .

RUN npm run build

FROM base AS development

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
COPY --chown=www-data:www-data . .

FROM base AS production

RUN install-php-extensions \
        pdo_mysql \
        redis \
        intl \
        bcmath \
        excimer \
        opcache \
        zip \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/local/bin/composer \
    --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY docker/configuration/Caddyfile /etc/frankenphp/Caddyfile
COPY --chown=www-data:www-data docker/scripts/production/entrypoint.sh /var/www/html/entrypoint.sh
COPY --from=assets --chown=www-data:www-data /usr/src/app/public/build ./public/build
COPY --chown=www-data:www-data . .

RUN --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/local/bin/composer \
    composer dump-autoload --no-dev --classmap-authoritative && \
    composer dump-env prod && \
    composer run-script --no-dev post-install-cmd && \
    rm -rf var && \
    echo "* * * * * /usr/local/bin/php /var/www/html/bin/console app:tasks-executor > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data && \
    echo "0 * * * * /usr/local/bin/php /var/www/html/bin/console app:statistics-collector > /dev/null 2>&1" >> /var/spool/cron/crontabs/www-data && \
    chmod +x /var/www/html/entrypoint.sh && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    printf '%s\n' \
      'expose_php = Off' \
      'post_max_size = 24M' \
      'upload_max_filesize = 20M' \
      'realpath_cache_ttl = 600' \
      'variables_order = "EGPCS"' \
      'date.timezone = "Europe/Paris"' \
      'session.name = __SECURE-PHPSESSID' \
      'session.use_strict_mode = 1' \
      'session.cookie_secure = 1' \
      'session.cookie_httponly = 1' \
      'session.cookie_samesite = Strict' \
      'opcache.validate_timestamps = 0' \
      'opcache.memory_consumption = 256' \
      'opcache.interned_strings_buffer = 16' \
      'opcache.max_accelerated_files = 20000' \
    > "$PHP_INI_DIR/conf.d/zz-custom.ini"
