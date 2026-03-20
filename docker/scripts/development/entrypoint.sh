#!/bin/sh

composer install --no-interaction

/usr/local/bin/php bin/console doctrine:database:create --no-interaction --if-not-exists
/usr/local/bin/php bin/console doctrine:schema:update --force
/usr/local/bin/php bin/console app:udp-server 127.0.0.1:8443 &

symfony server:start --allow-all-ip