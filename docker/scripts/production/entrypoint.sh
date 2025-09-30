#!/bin/sh

# Generate random secret and passphrase for security operations
sed -i "s#APP_SECRET=secret#APP_SECRET=$(openssl rand -base64 32)#g" .env && \
sed -i "s#SSL_PHRASE=passphrase#SSL_PHRASE=$(tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30)#g" .env

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Clear Symfony application cache
/usr/local/bin/php bin/console cache:clear

# Dump environment variables for production
composer dump-env prod

# Create database and update schema
/usr/local/bin/php bin/console doctrine:database:create --no-interaction --if-not-exists
/usr/local/bin/php bin/console doctrine:schema:update --force

# Start UDP server for incoming logs
/usr/local/bin/php bin/console app:udp-server 127.0.0.1:8443 &

# Start PHP-FPM daemon
php-fpm