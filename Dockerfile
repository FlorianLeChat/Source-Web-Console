# syntax=docker/dockerfile:1

# Use an customized image of Node.js
# https://hub.docker.com/_/node
ARG VERSION=8.2-apache
FROM node:lts-alpine

# Set the working directory to the website files
WORKDIR /usr/src/app

# Copy only files required to install dependencies
COPY --chown=node:node package*.json .

# Install all dependencies
# Use cache mount to speed up installation of existing dependencies
RUN --mount=type=cache,target=/usr/src/app/.npm \
	npm set cache /usr/src/app/.npm && \
	npm install

# Change ownership of the installed dependencies
RUN chown -R node:node /usr/src/app/node_modules

# Copy the remaining files AFTER installing dependencies
COPY --chown=node:node . .

# Build all static assets
RUN npm run build

# Remove all development dependencies
RUN npm prune --production

# Use an customized image of PHP
# https://hub.docker.com/_/php
FROM php:${VERSION}

# Install dependencies
ARG MANAGER=apt
RUN if [ $MANAGER = "apt" ]; then \
        apt update && apt install git cron -y; \
    else \
		echo https://dl-4.alpinelinux.org/alpine/latest-stable/community/ >> /etc/apk/repositories && \
		apk update && \
        apk add --no-cache git; \
    fi

# Install some PHP extensions
RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions zip pdo_mysql pdo_pgsql redis opcache intl

# Install Composer for dependency management
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Use the PHP production configuration
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

# Set the working directory to the website files
WORKDIR /var/www/html

# Copy only files required to install dependencies
COPY --chown=www-data:www-data composer*.json ./

# Install all dependencies
# Use cache mount to speed up installation of existing dependencies
RUN --mount=type=cache,target=.composer \
	composer install --no-dev --optimize-autoloader

# Add some cronjobs for Symfony custom commands
# https://github.com/webdevops/Dockerfile/issues/280#issuecomment-1311681838
RUN echo "* * * * * /usr/local/bin/php ./bin/console app:tasks-executor > /dev/null 2>&1" >> /var/spool/cron/crontabs/root
RUN echo "0 * * * * /usr/local/bin/php ./bin/console app:statistics-collector > /dev/null 2>&1" >> /var/spool/cron/crontabs/root

# Copy files from the previous stage
COPY --from=0 --chown=www-data:www-data /usr/src/app ./

# Set the document root to the public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Change current user to www-data
USER www-data

# Find and replace some default environment variables
RUN sed -i "s/APP_ENV=dev/APP_ENV=prod/g" .env

RUN sed -i "s/DATABASE_HOST=127.0.0.1/DATABASE_HOST=mariadb/g" .env
RUN sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" .env

RUN sed -i "s/DATABASE_USERNAME=username/DATABASE_USERNAME=source_web_console/g" .env
RUN sed -i "s/REDIS_USERNAME=username/REDIS_USERNAME=default/g" .env

RUN sed -i "s/DATABASE_PASSWORD=password/DATABASE_PASSWORD=$(cat /usr/src/app/docker/config/db_password.txt | tr -d '\n')/g" .env

# Dump autoloader class to optimize performance
# https://symfony.com/doc/current/deployment.html
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-env prod

# Use the PHP custom configuration (if exists)
RUN if [ -f "docker/php.ini" ]; then mv "docker/php.ini" "$PHP_INI_DIR/php.ini"; fi