# In French

## Installation

> [!WARNING]
> L'installation **sans** Docker nécessite d'avoir une base de données [compatible avec Doctrine](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html#introduction) pour la gestion des données du site Internet. Vous devez également être en possession d'un serveur SMTP pour l'envoi des courriels de création/connexion des comptes utilisateurs. Enfin, le site Internet traite un grand volume de données et utilise [Redis](https://redis.io/downloads/) comme solution de mise en cache pour enregistrer temporairement les données les plus fréquemment consultées.

### Développement local

- Installer [PHP LTS](https://www.php.net/downloads.php) (>8.2 ou plus) ;
- Installer [NodeJS LTS](https://nodejs.org/) (>20 ou plus) ;
- Installer [Symfony CLI](https://symfony.com/download) ;
- Installer les extensions PHP additionnelles suivantes : `zip`, `pdo_mysql`, `redis`, `opcache`, `intl`, `xdebug`, `bcmath`, `excimer` ;
- Installer les dépendances du projet avec les commandes `composer install` et `npm install` ;
- Modifier les [variables d'environnement](.env) pour la connexion à la base de données (`DATABASE_...`) ;
- Modifier les [variables d'environnement](.env) pour la connexion au serveur de cache (`CACHE_...`) ;
- Modifier les [variables d'environnement](.env) pour configurer le serveur de messagerie (`SMTP_...`) ;
- Générer une phrase de passe avec la commande `tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30; echo` (nécessite [bash](https://www.gnu.org/software/bash/)) ;
- Modifier la [variable d'environnement](.env) `SSL_PHRASE` avec la valeur générée à l'étape précédente ;
- Générer un *hash* en base64 avec la commande `openssl rand -base64 32` (nécessite [OpenSSL](https://openssl-library.org/source/)) ;
- Modifier la [variable d'environnement](.env) `APP_SECRET` avec la valeur générée à l'étape précédente ;
- *(Facultatif)* Exécuter la commande `php bin/console doctrine:database:create --no-interaction --if-not-exists` pour créer la base de données ;
- Exécuter la commande `php doctrine:schema:update --force` pour créer les tables dans la base de données ;
- Démarrer le serveur local Symfony avec la commande `symfony server:start` ;
- Dans un deuxième terminal, exécuter la commande `npm run watch` pour lancer la compilation automatique des fichiers *TypeScript* et *SASS* ;
- Dans un troisième terminal, exécuter la commande `php bin/console app:udp-server 127.0.0.1:8443` pour enregistrer les journaux d'événements des serveurs distants (**votre pare-feu et/ou votre routeur doivent être correctement configurés**) ;
- *(Facultatif)* Configurer une tâche planifiée pour exécuter la commande `php bin/console app:tasks-executor` pour l'[exécution des tâches planifiées des serveurs distants](src/Command/ScheduledTasksExecutor.php) ;
- *(Facultatif)* Configurer une tâche planifiée pour exécuter la commande `php bin/console app:statistics-collector` pour la [collecte périodique des statistiques d'utilisation](src/Command/ServerStatisticsCollector.php).

> [!TIP]
> Pour tester le projet, vous pouvez utiliser [Docker](https://www.docker.com/). Une fois installé, il suffit de lancer l'image Docker de développement à l'aide de la commande `docker compose -f compose.development.yml up --detach --build`. Lorsque le conteneur Docker est prêt, utilisez la commande `npm run watch` pour compiler automatiquement les fichiers *TypeScript* et *SASS* lors de chacune de vos modifications. Le site devrait être accessible à l'adresse suivante : http://localhost:8000/. 🐳

### Déploiement en production

- Installer [PHP LTS](https://www.php.net/downloads.php) (>8.2 ou plus) ;
- Installer [NodeJS LTS](https://nodejs.org/) (>20 ou plus) ;
- Installer les extensions PHP additionnelles suivantes : `zip`, `pdo_mysql`, `redis`, `opcache`, `intl`, `bcmath`, `excimer` ;
- Installer les dépendances du projet avec les commandes `composer install --no-dev --optimize-autoloader` et `npm install` ;
- Modifier la [variable d'environnement](.env) `APP_ENV` sur `prod` ;
- Modifier les [variables d'environnement](.env) pour la connexion à la base de données (`DATABASE_...`) ;
- Modifier les [variables d'environnement](.env) pour la connexion au serveur de cache (`CACHE_...`) ;
- Modifier les [variables d'environnement](.env) pour configurer le serveur de messagerie (`SMTP_...`) ;
- Générer une phrase de passe avec la commande `tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30; echo` (nécessite [bash](https://www.gnu.org/software/bash/)) ;
- Modifier la [variable d'environnement](.env) `SSL_PHRASE` avec la valeur générée à l'étape précédente ;
- Générer un *hash* en base64 avec la commande `openssl rand -base64 32` (nécessite [OpenSSL](https://openssl-library.org/source/)) ;
- Modifier la [variable d'environnement](.env) `APP_SECRET` avec la valeur générée à l'étape précédente ;
- Compiler les fichiers statiques du site Internet avec la commande `npm run build` ;
- Supprimer les dépendances de développement avec la commande `npm prune --omit=dev` ;
- Exécuter la commande `php bin/console cache:clear` pour vider le cache de fichiers utilisé par Symfony ;
- Exécuter la commande `composer dump-env prod` pour transformer les variables d'environnement en variables statiques utilisables par PHP ;
- *(Facultatif)* Exécuter la commande `php bin/console doctrine:database:create --no-interaction --if-not-exists` pour créer une base de données ;
- Exécuter la commande `php doctrine:schema:update --force` pour créer les tables dans la base de données ;
- Utiliser un serveur Web pour servir les scripts PHP et les fichiers statiques générés dans les étapes précédentes ;
- Exécuter la commande `php bin/console app:udp-server 127.0.0.1:8443` pour enregistrer les journaux d'événements des serveurs distants (**votre pare-feu et/ou votre routeur doivent être correctement configurés**) ;
- Configurer une tâche planifiée pour exécuter la commande `php bin/console app:tasks-executor` pour l'[exécution des tâches planifiées des serveurs distants](src/Command/ScheduledTasksExecutor.php) ;
- Configurer une tâche planifiée pour exécuter la commande `php bin/console app:statistics-collector` pour la [collecte périodique des statistiques d'utilisation](src/Command/ServerStatisticsCollector.php).

> [!CAUTION]
> Le déploiement en environnement de production (**avec ou sans Docker**) **nécessite des connaissances approfondies pour déployer, optimiser et sécuriser correctement votre installation** afin d'éviter toute conséquence indésirable. ⚠️

# In English

## Setup

> [!WARNING]
> Installation **without** Docker requires having a [Doctrine-compatible database](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html#introduction) for managing website data. You must also have access to an SMTP server for sending emails related to user account creation/login. Finally, the website processes a large volume of data and uses [Redis](https://redis.io/downloads/) as a caching solution to temporarily store the most frequently accessed data.

### Local Development

- Install [PHP LTS](https://www.php.net/downloads.php) (>8.2 or higher) ;
- Install [NodeJS LTS](https://nodejs.org/) (>20 or higher) ;
- Install [Symfony CLI](https://symfony.com/download) ;
- Install the following additional PHP extensions: `zip`, `pdo_mysql`, `redis`, `opcache`, `intl`, `xdebug`, `bcmath`, `excimer` ;
- Install project dependencies using `composer install` and `npm install` ;
- Set [environment variables](.env) for database connection (`DATABASE_...`) ;
- Set [environment variables](.env) for cache server connection (`CACHE_...`) ;
- Set [environment variables](.env) to configure mail server (`SMTP_...`) ;
- Generate a passphrase using `tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30; echo` (requires [bash](https://www.gnu.org/software/bash/)) ;
- Set `SSL_PHRASE` [environment variable](.env) with value generated in the previous step ;
- Generate a base64 hash using `openssl rand -base64 32` (requires [OpenSSL](https://openssl-library.org/source/)) ;
- Set `APP_SECRET` [environment variable](.env) with value generated in the previous step ;
- *(Optional)* Run `php bin/console doctrine:database:create --no-interaction --if-not-exists` to create a database ;
- Run `php doctrine:schema:update --force` to create tables in the database ;
- Start local Symfony server with `symfony server:start` ;
- In a second terminal, run `npm run watch` to start automatic compilation of *TypeScript* and *SASS* files ;
- In a third terminal, run `php bin/console app:udp-server 127.0.0.1:8443` to log event data from remote servers (**firewall and/or router must be properly configured**) ;
- *(Optional)* Configure a scheduled task to run `php bin/console app:tasks-executor` for [executing scheduled tasks on remote servers](src/Command/ScheduledTasksExecutor.php) ;
- *(Optional)* Configure a scheduled task to run `php bin/console app:statistics-collector` for [periodic collection of usage statistics](src/Command/ServerStatisticsCollector.php).

> [!TIP]
> To test the project, you can use [Docker](https://www.docker.com/). Once installed, simply start the development Docker image using the command `docker compose -f compose.development.yml up --detach --build`. When the Docker container is ready, run the `npm run watch` command to automatically compile all *TypeScript* and *SASS* files each time you make changes. The website should then be accessible at the following address: http://localhost:8000/. 🐳

### Production Deployment

- Install [PHP LTS](https://www.php.net/downloads.php) (>8.2 or higher) ;
- Install [NodeJS LTS](https://nodejs.org/) (>20 or higher) ;
- Install the following additional PHP extensions: `zip`, `pdo_mysql`, `redis`, `opcache`, `intl`, `bcmath`, `excimer` ;
- Install project dependencies with `composer install --no-dev --optimize-autoloader` and `npm install` ;
- Set `APP_ENV` [environment variable](.env) to `prod` ;
- Set [environment variables](.env) for database connection (`DATABASE_...`) ;
- Set [environment variables](.env) for cache server connection (`CACHE_...`) ;
- Set [environment variables](.env) to configure mail server (`SMTP_...`) ;
- Generate a passphrase using `tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 30; echo` (requires [bash](https://www.gnu.org/software/bash/)) ;
- Set `SSL_PHRASE` [environment variable](.env) with value generated in the previous step ;
- Generate a base64 hash using `openssl rand -base64 32` (requires [OpenSSL](https://openssl-library.org/source/)) ;
- Set `APP_SECRET` [environment variable](.env) with value generated in the previous step ;
- Compile static website files with `npm run build` ;
- Remove development dependencies with `npm prune --omit=dev` ;
- Run `php bin/console cache:clear` to clear Symfony's file cache ;
- Run `composer dump-env prod` to convert environment variables into static variables usable by PHP ;
- *(Optional)* Run `php bin/console doctrine:database:create --no-interaction --if-not-exists` to create a database ;
- Run `php doctrine:schema:update --force` to create tables in the database ;
- Use a web server to serve PHP scripts and static files generated in previous steps ;
- Run `php bin/console app:udp-server 127.0.0.1:8443` to log event data from remote servers (**firewall and/or router must be properly configured**) ;
- Configure a scheduled task to run `php bin/console app:tasks-executor` for [executing scheduled tasks on remote servers](src/Command/ScheduledTasksExecutor.php) ;
- Configure a scheduled task to run `php bin/console app:statistics-collector` for [periodic collection of usage statistics](src/Command/ServerStatisticsCollector.php).

> [!CAUTION]
> Deploying in a production environment (**with or without Docker**) **requires advanced knowledge to properly deploy, optimize, and secure your installation** in order to avoid any unwanted consequences. ⚠️
