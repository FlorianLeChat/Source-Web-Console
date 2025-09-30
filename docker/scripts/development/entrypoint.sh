#!/bin/sh

# Generate random secret and passphrase for security operations
sed -i "s#APP_SECRET=secret#APP_SECRET=$(openssl rand -base64 32)#g" .env && \
sed -i "s#SSL_PHRASE=passphrase#SSL_PHRASE=$(tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30)#g" .env

# Install Composer dependencies
composer install

# Create database and update schema
/usr/local/bin/php bin/console doctrine:database:create --no-interaction --if-not-exists
/usr/local/bin/php bin/console doctrine:schema:update --force

# Start UDP server for incoming logs
/usr/local/bin/php bin/console app:udp-server 127.0.0.1:8443 &

# Clear previous Symfony local server cache
rm -rf ~/.symfony5/

# Start Symfony server to listen on all interfaces
symfony server:start --allow-all-ip