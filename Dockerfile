# syntax=docker/dockerfile:1

# Use an customized image of Node.js
# https://hub.docker.com/_/node
FROM node:lts-alpine

# Set the working directory to the website files
WORKDIR /usr/src/app

# Copy only files required to install dependencies
COPY package*.json .

# Install all dependencies
# Use cache mount to speed up installation of existing dependencies
RUN --mount=type=cache,target=/usr/src/app/.npm \
	npm set cache /usr/src/app/.npm && \
	npm install

# Copy the remaining files AFTER installing dependencies
COPY . .

# Build all static assets
RUN npm run build

# Use an customized image of PHP 8.2 with Nginx
# https://github.com/webdevops/Dockerfile/blob/master/docker/php-nginx/8.2-alpine/Dockerfile
FROM webdevops/php-nginx:8.2-alpine

# Add wait script to wait for other services to be ready
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.12.0/wait /wait
RUN chmod +x /wait

# Add startup commands to the entrypoint
# https://symfony.com/doc/current/deployment.html / https://symfony.com/doc/current/setup/file_permissions.html
RUN echo "/wait && /usr/local/bin/php /app/bin/console cache:clear && \
	cd /app/var && chmod 777 cache/prod/ && chmod 777 log/ && \
	/usr/local/bin/php /app/bin/console doctrine:database:create --no-interaction && \
	/usr/local/bin/php /app/bin/console doctrine:schema:create --no-interaction && \
	/usr/local/bin/php /app/bin/console app:udp-server 127.0.0.1:81 &" >> /opt/docker/provision/entrypoint.d/25-app.sh

RUN chmod +x /opt/docker/provision/entrypoint.d/25-app.sh

# Set the working directory to the website files
WORKDIR /app

# Copy only files required to install dependencies
COPY composer*.json ./

# Install all dependencies
# Use cache mount to speed up installation of existing dependencies
RUN --mount=type=cache,target=/app/.composer \
	composer install --no-dev --optimize-autoloader

# Copy files from the previous stage
COPY --from=0 /usr/src/app ./

# Find and replace some default environment variables
RUN sed -i "s/APP_ENV=dev/APP_ENV=prod/g" .env

RUN sed -i "s/DATABASE_HOST=127.0.0.1/DATABASE_HOST=database/g" .env
RUN sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=cache/g" .env

ARG MARIADB_PORT
RUN sed -i "s/DATABASE_PORT=3306/DATABASE_PORT=${MARIADB_PORT}/g" .env

ARG MARIADB_DATABASE
RUN sed -i "s/DATABASE_NAME=source_web_console/DATABASE_NAME=${MARIADB_DATABASE}/g" .env

ARG MARIADB_USER
RUN sed -i "s/DATABASE_USERNAME=username/DATABASE_USERNAME=${MARIADB_USER}/g" .env
RUN sed -i "s/REDIS_USERNAME=username/REDIS_USERNAME=default/g" .env

ARG MARIADB_PASSWORD
RUN sed -i "s/DATABASE_PASSWORD=password/DATABASE_PASSWORD=${MARIADB_PASSWORD}/g" .env

# Dump autoloader class to optimize performance
# https://symfony.com/doc/current/deployment.html
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-env prod

# Add some cronjobs for Symfony custom commands
# https://github.com/webdevops/Dockerfile/issues/280#issuecomment-1311681838
RUN echo "* * * * * /usr/local/bin/php /app/bin/console app:tasks-executor > /dev/null 2>&1" >> /var/spool/cron/crontabs/root
RUN echo "0 * * * * /usr/local/bin/php /app/bin/console app:statistics-collector > /dev/null 2>&1" >> /var/spool/cron/crontabs/root