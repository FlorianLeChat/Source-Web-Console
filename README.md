# 🕹️ Source Web Console

![image](https://user-images.githubusercontent.com/26360935/165751507-f0c82948-3a4f-4220-9817-fc04769480ad.svg)

## In French

C'est un projet réalisé durant mes études afin de permettre de gérer les serveurs dédiés de jeu utilisant le protocole **[Source RCON](https://developer.valvesoftware.com/wiki/Source_RCON_Protocol)** à travers une interface graphique. La réalisation de ce projet est intervenue après celui de mon **[portfolio](https://github.com/FlorianLeChat/Portfolio)**, qui lui aussi a reçu une refonte graphique et technologique.

À la fin de la première version du projet en utilisant seulement des langages et technologies natives d'Internet (branche `no-symfony`), la dernière version reprend le code d'origine tout en basculant sur le *framework* [Symfony](https://symfony.com/) pour profiter d'améliorations techniques, de performances mais aussi de sécurité. De plus, même si le souffre encore d'une dette technologique assez importante par l'absence de *framework* pour gérer la partie interface, le code d'origine a été migrée vers [TypeScript](https://www.typescriptlang.org/) pour une meilleure robustesse.

<ins>Voici les exigences pour exécuter le site Internet</ins> :
* [**Toute** version de PHP maintenue](https://www.php.net/supported-versions.php)
* [**Toute** version de NodeJS LTS maintenue](https://github.com/nodejs/release#release-schedule)
* [**Toute** version de Redis maintenue](https://docs.redis.com/latest/rs/installing-upgrading/install/plan-deployment/supported-platforms/)
* [**Toute** bases de données supportée par Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/platforms.html)

⚠️ [**LISEZ AVANT UTILISATION**] Ce projet est conçu pour fonctionner dans un environnement de production mais celui-ci doit être considéré comme une « preuve de concept » pour mes études concernant l'utilisation de technologies Internet natives pour communiquer avec le protocole RCON, si vous comptez utiliser ce genre de sites pour administrer votre serveur, je ne peux que vous conseiller l'excellent [**Pterodactyl**](https://pterodactyl.io/).

**Une image Docker est aussi disponible pour tester ce projet pour les personnes les plus expérimentées ! 🐳**

Par soucis de simplicité, l'entièreté du code est commentée dans ma langue natale (en français) mais il sera traduit dans un futur proche si des contributeurs étrangers viennent s'ajouter au projet.

___

## In English

This is a project made during my studies to manage dedicated game servers using the **[Source RCON](https://developer.valvesoftware.com/wiki/Source_RCON_Protocol)** protocol through a graphical interface. The realization of this project came after that of my **[portfolio](https://github.com/FlorianLeChat/Portfolio)**, which also received a graphic and technological overhaul.

Following the end of the first project version using only native Web languages and technologies (`no-symfony` branch), the latest version reuses the original code while migrating to the [Symfony](https://symfony.com/) framework to enjoy technical, performance and security improvements. Even though it still suffers from a significant technological debt due to the absence of a framework to manage the front-end, the original code has been migrated to [TypeScript](https://www.typescriptlang.org/) for greater robustness.

<ins>Here are the requirements to run the website</ins>:
* [**Any** maintained PHP versions](https://www.php.net/supported-versions.php)
* [**Any** maintained NodeJS LTS versions](https://github.com/nodejs/release#release-schedule)
* [**Any** maintained Redis versions](https://docs.redis.com/latest/rs/installing-upgrading/install/plan-deployment/supported-platforms/)
* [**Any** databases supported by Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/platforms.html)

⚠️ [**PLEASE READ BEFORE USING**] This project is intended to run in a production environment, but it should be considered as a "proof of concept" for my studies concerning the usage of native Web technologies to communicate with RCON protocol. If you intend to use this kind of website to manage your server, I advise you to consider using the excellent [**Pterodactyl**](https://pterodactyl.io/).

**A Docker image is also available to test this project for the most skilled people! 🐳**

To keep it simple, the whole code is commented in my native language (French) but it will be translated in the near future if foreign contributors come to the project.

![image](https://github.com/FlorianLeChat/Source-Web-Console/assets/26360935/0aaed929-a530-4c41-bdbc-2e05eab82e9e)