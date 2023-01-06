# syntax=docker/dockerfile:1

# Use an customized image of PHP 8.2 with Nginx
# https://github.com/webdevops/Dockerfile/blob/master/docker/php-nginx/8.2-alpine/Dockerfile
ARG PHP_VERSION
FROM webdevops/php-nginx:${PHP_VERSION}-alpine

# Copy the website files to the container
COPY ./ /app

# Modify the config.php file to use the environment variables
# (Since we don't have an .env file for this project...)
RUN sed -i "s/localhost/database/g" /app/config.php

ARG MARIADB_DATABASE
RUN sed -i "s/source_web_console/${MARIADB_DATABASE}/g" /app/config.php

ARG MARIADB_USER
RUN sed -i "s/username/${MARIADB_USER}/g" /app/config.php

ARG MARIADB_PASSWORD
RUN sed -i "s/password/${MARIADB_PASSWORD}/g" /app/config.php

ARG MARIADB_PORT
RUN sed -i "s/3306/${MARIADB_PORT}/g" /app/config.php

# Install Composer and run it to install the dependencies
RUN composer install -d /app

# Remove the database.sql file (it was previously imported into the docker compose file)
RUN rm ./app/database.sql

# Add a cronjob to run every minute (automated tasks for registered game servers)
# Default cronjobs are broken in Alpine, so we have to use a different method : https://github.com/webdevops/Dockerfile/issues/280#issuecomment-1311681838
RUN echo "* * * * * /usr/local/bin/php /app/includes/controllers/server_tasks.php > /dev/null 2>&1" >> /var/spool/cron/crontabs/root