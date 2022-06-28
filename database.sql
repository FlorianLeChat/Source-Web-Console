-- phpMyAdmin SQL Dump
-- version 5.1.4deb1~bpo11+1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 28 juin 2022 à 09:53
-- Version du serveur : 10.5.15-MariaDB-0+deb11u1
-- Version de PHP : 8.0.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `source_web_console`
--

-- --------------------------------------------------------

--
-- Structure de la table `commands`
--

DROP TABLE IF EXISTS `commands`;
CREATE TABLE IF NOT EXISTS `commands` (
  `command_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` smallint(5) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`command_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `contact`
--

DROP TABLE IF EXISTS `contact`;
CREATE TABLE IF NOT EXISTS `contact` (
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(40) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `content` varchar(4000) NOT NULL,
  PRIMARY KEY (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `server_id` mediumint(8) UNSIGNED NOT NULL,
  `action_type` text NOT NULL,
  PRIMARY KEY (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `servers`
--

DROP TABLE IF EXISTS `servers`;
CREATE TABLE IF NOT EXISTS `servers` (
  `server_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` smallint(5) UNSIGNED NOT NULL,
  `client_address` varchar(15) NOT NULL,
  `client_port` char(5) NOT NULL,
  `admin_address` varchar(15) DEFAULT NULL,
  `admin_port` char(5) DEFAULT NULL,
  `admin_password` longtext DEFAULT NULL,
  `game_id` int(11) NOT NULL,
  `secure_only` tinyint(1) NOT NULL DEFAULT 0,
  `auto_connect` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `storage`
--

DROP TABLE IF EXISTS `storage`;
CREATE TABLE IF NOT EXISTS `storage` (
  `server_id` mediumint(8) UNSIGNED NOT NULL,
  `protocol` varchar(4) NOT NULL DEFAULT 'FTP',
  `host` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` char(60) NOT NULL,
  `port` char(5) NOT NULL,
  PRIMARY KEY (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_id` mediumint(8) UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `action` tinytext NOT NULL,
  `state` varchar(10) NOT NULL DEFAULT 'WAITING',
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `translations`
--

DROP TABLE IF EXISTS `translations`;
CREATE TABLE IF NOT EXISTS `translations` (
  `identifier` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_string` varchar(40) NOT NULL,
  `translated_string` mediumtext NOT NULL,
  `target_language` char(2) NOT NULL,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=383 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `translations`
--

INSERT INTO `translations` (`identifier`, `source_string`, `translated_string`, `target_language`) VALUES
(1, 'footer_home', 'Accueil', 'FR'),
(2, 'footer_home', 'Home', 'EN'),
(3, 'footer_legal', 'Mentions légales', 'FR'),
(4, 'footer_legal', 'Legal notice', 'EN'),
(5, 'footer_contact', 'Contact', 'FR'),
(6, 'footer_support', 'Assistance', 'FR'),
(7, 'footer_support', 'Support', 'EN'),
(8, 'index_signup', 'Inscription', 'FR'),
(9, 'index_signup', 'Register', 'EN'),
(10, 'index_signin', 'Connexion', 'FR'),
(11, 'index_signin', 'Login', 'EN'),
(12, 'head_no_javascript', 'Votre navigateur ne supporte pas ou refuse de charger le JavaScript.', 'FR'),
(13, 'head_no_javascript', 'Your browser does not support or refuses to load JavaScript.', 'EN'),
(14, 'language_fr', 'Français', 'FR'),
(15, 'language_fr', 'French', 'EN'),
(16, 'language_en', 'Anglais', 'FR'),
(17, 'language_en', 'English', 'EN'),
(18, 'index_tips', 'Vous êtes sur la page d\'accueil du site. Vous pourrez retrouver la liste des fonctionnalités ainsi que la possibilité de vous inscrire et que de vous connecter à un compte utilisateur.', 'FR'),
(19, 'index_tips', 'You are on the home page of the website. You can find the list of features along with the possibility to register and connect to a user account.', 'EN'),
(20, 'index_feature_description_1', 'Bienvenue sur l\'interface d\'administration en ligne pour serveurs multi-joueurs exécutant le moteur Source de Valve Corporation. Le site propose plusieurs outils performants et simples d\'utilisation pour gérer facilement ses serveurs. Il est compatible avec les jeux populaires du moment comme **Garry\'s Mod**, **Minecraft** et **Counter-Strike : Global Offensive** !', 'FR'),
(21, 'index_feature_description_1', 'Welcome to the online administration interface for multiplayer servers running the Source engine from Valve Corporation. The website offers several powerful and easy-to-use tools to easily manage your servers. It is compatible with popular games like **Garry\'s Mod**, **Minecraft** and **Counter-Strike: Global Offensive**!', 'EN'),
(22, 'index_feature_description_2', 'À la place des solutions actuelles et concurrences, le site vous permettra d\'exploiter le protocole de communication RCON sans aucun interruption ainsi que de façon rapide et facile d\'accès. De plus, le site possède plusieurs optimisations pour réduire les temps d\'accès et de chargement traditionnellement longs chez les hébergeurs les plus connus.', 'FR'),
(23, 'index_feature_description_2', 'Instead of the current and competing solutions, the website will allow you to use the RCON communication protocol without any interruption and in a fast and easy way. In addition, the website has several optimizations to reduce the access and loading times traditionally long with the most famous hosts.', 'EN'),
(24, 'index_feature_description_3', 'Le protocole de communication RCON subit plusieurs modifications et mises à jour chaque année pour rendre plus performant les interactions entre les tiers (nous) et l\'API distant utilisé sur les serveurs de jeux. De ce fait, toutes les solutions actuelles sont parfois rendues obsolètes ou hors-service pendant des mois voir des années car les créateurs doivent réviser leurs outils de communication. Le site utilise de nouveaux moyens afin d\'être presque indépendant aux mises à jour et de pouvoir continuer de fonctionner malgré des modifications importantes réalisées par Valve.', 'FR'),
(25, 'index_feature_description_3', 'The RCON communication protocol is subject to several modifications and updates every year to improve the performance of interactions between third parties (us) and the remote API used on game servers. As a result, all current solutions are sometimes rendered obsolete or out of service for months or even years as creators need to revise their communication tools. The website uses new ways to be practically independent from updates and to be able to continue to work despite major changes made by Valve.', 'EN'),
(28, 'head_title', 'Source Web Console', 'FR'),
(29, 'head_description', 'Interface d\'administration en ligne pour serveurs multi-joueurs exécutant le moteur Source de Valve.', 'FR'),
(30, 'head_description', 'Web-based administration interface for multiplayer servers running Valve\'s Source engine.', 'EN'),
(31, 'header_title', 'Administration', 'FR'),
(32, 'header_subtitle_dashboard', 'Tableau de bord', 'FR'),
(33, 'header_subtitle_dashboard', 'Dashboard', 'EN'),
(34, 'header_subtitle_statistics', 'Statistiques', 'FR'),
(35, 'header_subtitle_statistics', 'Statistics', 'EN'),
(36, 'header_subtitle_configuration', 'Configuration', 'FR'),
(37, 'header_subtitle_actions', 'Actions et commandes', 'FR'),
(38, 'header_subtitle_actions', 'Actions and commands', 'EN'),
(39, 'header_subtitle_console', 'Console interactive', 'FR'),
(40, 'header_subtitle_console', 'Interactive console', 'EN'),
(41, 'header_subtitle_tasks', 'Tâches planifiées', 'FR'),
(42, 'header_subtitle_tasks', 'Scheduled tasks', 'EN'),
(43, 'header_subtitle_help', 'Assistance utilisateur', 'FR'),
(44, 'header_subtitle_help', 'User support', 'EN'),
(45, 'form_signup_description_user', 'La première partie du formulaire d\'inscription concerne les informations personnelles du compte utilisateur. Vous devez indiquer un nom d\'utilisateur unique et un mot de passe suffisamment long et complexe pour être considéré comme sécurisé.', 'FR'),
(46, 'form_signup_description_user', 'The first part of the registration form is the user account details. You must provide a unique username and a password that is long and complex enough to be considered secure.', 'EN'),
(47, 'form_rgpd_warning', 'En remplissant ce formulaire, vous acceptez tacitement le traitement de vos données personnelles en respect des lois nationales et européennes en vigueur (RGPD).', 'FR'),
(48, 'form_rgpd_warning', 'By filling in this form, you tacitly agree to the processing of your personal data in compliance with the national and European laws in effect (RGPD).', 'EN'),
(49, 'form_signup_username', 'Choisissez un nom d\'utilisateur', 'FR'),
(50, 'form_signup_username', 'Choose a username', 'EN'),
(51, 'form_signup_password', 'Choisissez un mot de passe', 'FR'),
(52, 'form_signup_password', 'Choose a password', 'EN'),
(53, 'form_clear_password', 'Afficher le mot de passe', 'FR'),
(54, 'form_clear_password', 'Reveal the password', 'EN'),
(55, 'form_signup_generation', 'Générer un mot de passe sécurisé', 'FR'),
(56, 'form_signup_generation', 'Generate a secure password', 'EN'),
(57, 'form_cancel_button', 'Annuler', 'FR'),
(58, 'form_cancel_button', 'Cancel', 'EN'),
(59, 'form_validate_button', 'Valider', 'FR'),
(60, 'form_validate_button', 'Validate', 'EN'),
(61, 'form_signup_description_server', 'La deuxième et dernière partie du formulaire concerne le remplissage des informations d\'authentification pour la connexion au premier serveur. Seuls les informations client sont requises alors que les informations administrateurs peuvent être facultatives. Certaines actions du site nécessiteront néanmoins les autorisations administrateur.', 'FR'),
(62, 'form_signup_description_server', 'The second and last part of the form concerns the filling of authentication information for the connection to the first server. Only the client information is required while the administrator information is optional. Some actions of the website will however require administrator permissions.', 'EN'),
(63, 'form_signup_client_title', 'Saisissez les informations client', 'FR'),
(64, 'form_signup_client_title', 'Enter the client credentials', 'EN'),
(65, 'form_signup_client_description', 'Les informations client concernent celles qui sont utilisés par les joueurs (client) pour se connecter classiquement au serveur. Ces informations permettent de lire les données générales du site sans réaliser d\'actions avancées.', 'FR'),
(66, 'form_signup_client_description', 'The client information is the data used by the players (client) to connect to the server in the conventional way. This information allows to read the overall information of the website without performing any advanced actions.', 'EN'),
(67, 'form_signup_admin_description', 'Les informations administrateur concernent celles qui permettent aux systèmes d\'administrateur de se connecter à l\'API du module d\'administration de l\'instance. Cela permet de réaliser des actions et commandes à distance sans l\'intervention d\'une personne.', 'FR'),
(68, 'form_signup_admin_description', 'The administrator information is the data required for the control systems to connect to the API of the admin module of the server. This provides the ability to perform actions and commands remotely without the intervention of anyone.', 'EN'),
(69, 'form_signup_admin_title', 'Saisissez les informations administrateur', 'FR'),
(70, 'form_signup_admin_title', 'Enter the administrator credentials', 'EN'),
(71, 'form_signup_secure_only', 'Accepter uniquement les connexions sécurisées', 'FR'),
(72, 'form_signup_secure_only', 'Only accept secure connections', 'EN'),
(73, 'form_signup_auto_connect', 'Forcer la connexion automatique', 'FR'),
(74, 'form_signup_auto_connect', 'Force automatic connection', 'EN'),
(75, 'form_signin_description', 'Ce formulaire vous permet la connexion à votre compte utilisateur grâce à une combinaison d\'un nom d\'utilisateur et un mot de passe définie lors de l\'inscription. Vous retrouverez également la possibilité de réinitialiser votre mot de passe mais également de vous connecter grâce à un accès unique.', 'FR'),
(76, 'form_signin_description', 'This form provides you with the ability to connect to your user account using a combination of a username and a password defined during the registration process. You will also find the possibility to reset your password but also to connect through a unique access.', 'EN'),
(77, 'form_signin_username', 'Saisissez votre nom d\'utilisateur', 'FR'),
(78, 'form_signin_username', 'Enter your username', 'EN'),
(79, 'form_signin_password', 'Saisissez votre mot de passe', 'FR'),
(80, 'form_signin_password', 'Enter your password', 'EN'),
(81, 'form_remember_me', 'Se souvenir de moi', 'FR'),
(82, 'form_remember_me', 'Remember me', 'EN'),
(83, 'form_signin_links', 'Pas de compte ? [url=javascript:void(0);](Inscrivez-vous) ou passez à une [url=javascript:void(0);](connexion unique).\nSinon, vous avez peut-être [url=javascript:void(0);](perdu votre mot de passe) ?', 'FR'),
(84, 'form_signin_links', 'No account? [url=javascript:void(0);](Register) or switch to a [url=javascript:void(0);](one-shot signin).\nIf not, you may have [url=javascript:void(0);](lost your password)?', 'EN'),
(85, 'form_contact_description', 'Ce formulaire vous permet d\'envoyer un message d\'assistance aux administrateurs du site. Vous recevrez une réponse par l\'adresse électronique renseignée d\'ici quelques heures/jours. Tout abus sera sévèrement sanctionné.', 'FR'),
(86, 'form_contact_description', 'This form lets you send a support message to the website administrators. You will receive an answer by the email address you entered within a few hours/days. Any abuse will be prosecuted.', 'EN'),
(87, 'form_contact_success', 'Votre message a bien été envoyé. Vous receverez une réponse d\'ici peu.', 'FR'),
(88, 'form_contact_success', 'Your message has been sent. You will receive a reply shortly.', 'EN'),
(89, 'form_contact_email', 'Saisissez votre adresse électronique', 'FR'),
(90, 'form_contact_email', 'Enter your email address', 'EN'),
(91, 'form_contact_subject', 'Sélectionnez le sujet du contact', 'FR'),
(92, 'form_contact_subject', 'Select the subject', 'EN'),
(93, 'form_contact_subject_1', 'J\'ai un problème avec l\'inscription d\'un compte', 'FR'),
(94, 'form_contact_subject_1', 'I have an issue with the account registration', 'EN'),
(95, 'form_contact_subject_2', 'J\'ai un problème avec la connexion à mon compte', 'FR'),
(96, 'form_contact_subject_2', 'I have an issue with logging into my account', 'EN'),
(97, 'form_contact_subject_3', 'J\'ai une question avec la gestion de mon serveur', 'FR'),
(98, 'form_contact_subject_3', 'I have a question with my server administration', 'EN'),
(99, 'form_contact_content', 'Indiquez le contenu de votre message', 'FR'),
(100, 'form_contact_content', 'Specify the content of your message', 'EN'),
(101, 'form_client_check_failed', 'Le champ « $1 » doit avoir une taille comprise entre $2 et $3 caractères.', 'FR'),
(102, 'form_client_check_failed', 'The \"$1\" field must be at least $2 and less than $3 characters long.', 'EN'),
(103, 'form_server_check_failed', 'Les données renseignées n\'ont pas permis de réaliser cette requête.', 'FR'),
(104, 'form_server_check_failed', 'The information provided did not allow this query to be completed.', 'EN'),
(105, 'form_capslock_warning', 'Les touches majuscules sont activées.', 'FR'),
(106, 'form_capslock_warning', 'The caps lock keys are activated.', 'EN'),
(107, 'header_search_title', 'Actions rapides', 'FR'),
(108, 'header_search_title', 'Quick actions', 'EN'),
(109, 'header_search_placeholder', 'Recherche...', 'FR'),
(110, 'header_search_placeholder', 'Search...', 'EN'),
(111, 'header_user_status', 'Connecté sous\r\n%s', 'FR'),
(112, 'header_user_status', 'Connecté sous\r\n%s', 'EN'),
(113, 'header_user_status', 'Connected as\r\n%s', 'EN'),
(114, 'dashboard_description', 'Le tableau de page est la page principale du site. Il permet de vous donner un aperçu immédiat et en temps réel de ce qu\'il se passe sur le serveur et les actions que vous avez réalisé dernièrement. De plus, le tableau de bord permet l\'exécution de commandes rapides et le changement dynamique entre chacun de vos serveurs enregistrés.', 'FR'),
(115, 'dashboard_description', 'The page table is the main page of the website. It gives you an immediate and real-time overview of what is happening on the server and the actions you have performed lately. In addition, the dashboard allows for quick command execution and dynamic switching between each of your registered servers.', 'EN'),
(116, 'dashboard_tips', 'Vous êtes sur le tableau de bord. Sur cette page, vous pouvez voir l\'état général de votre serveur ainsi que réaliser certaines actions rapides. De cette manière, vous avez un oeil sur l\'ensemble de votre infrastructure.', 'FR'),
(117, 'dashboard_tips', 'You are on the dashboard. On this page you can see the global state of your server and perform some quick actions. This way, you have an eye on your entire infrastructure.', 'EN'),
(118, 'header_navigation', 'Navigation', 'FR'),
(119, 'dashboard_servers', 'Liste des serveurs', 'FR'),
(120, 'dashboard_servers', 'Servers list', 'EN'),
(121, 'dashboard_state', 'État général', 'FR'),
(122, 'dashboard_state', 'General state', 'EN'),
(123, 'dashboard_actions', 'Dernières actions', 'FR'),
(124, 'dashboard_actions', 'Last actions', 'EN'),
(125, 'global_shutdown_title', 'Arrêter', 'FR'),
(126, 'global_shutdown_title', 'Shutdown', 'EN'),
(127, 'global_shutdown_subtitle', 'Double-cliquez pour forcer l\'arrêt', 'FR'),
(128, 'global_shutdown_subtitle', 'Double-click to force shutdown', 'EN'),
(129, 'global_restart_title', 'Redémarrer', 'FR'),
(130, 'global_restart_title', 'Restart', 'EN'),
(131, 'global_update_title', 'Mettre à jour', 'FR'),
(132, 'global_update_title', 'Update', 'EN'),
(133, 'global_service_title', 'Maintenance', 'FR'),
(135, 'statistics_description', 'La page des statistiques est une page permettant de visualiser les informations collectées automatiquement par l\'infrastructure du site. Ces informations sont bien évidemment rendues anonymes et enregistrées en toute sécurité pour pouvoir les visualiser.', 'FR'),
(136, 'statistics_description', 'The statistics page is a page allowing to visualize the information collected automatically by the infrastructure of the website. This information is of course anonymized and saved in a safe place for viewing.', 'EN'),
(137, 'statistics_orientation', 'Veuillez tourner votre appareil.', 'FR'),
(138, 'statistics_orientation', 'Please rotate your device.', 'EN'),
(139, 'statistics_tips', 'Vous êtes sur la page des statistiques. Vous pouvez retrouver ici même toutes les statistiques disponibles à propos de votre serveur durant les dernières 24 heures. Ces statistiques sont automatisées et anonymes.', 'FR'),
(140, 'statistics_tips', 'You are on the statistics page. Here you can find all available statistics about your server during the last 24 hours. These statistics are automated and anonymous.', 'EN'),
(141, 'configuration_description', 'La page de configuration est une page spéciale nécessitant pour l\'utilisateur d\'indiquer des informations de connexion supplémentaires pour débloquer des fonctionnalités additionnelles (données FTP/SFTP). Celles-ci permettent une configuration plus poussée des options et de la configuration générale du serveur normalement inaccessible par le système standard.', 'FR'),
(142, 'configuration_description', 'The configuration page is a special page requiring the user to indicate additional login information to unlock additional functionality (FTP/SFTP data). These allow for further configuration of options and general server setup normally inaccessible by the default system.', 'EN'),
(143, 'configuration_tips', 'Vous êtes sur la page de la configuration général de votre serveur. Si vous indiquez les informations d\'authentification de votre serveur FTP ou SFTP, le site peut proposer une configuration avancée de votre serveur en respectant l\'anonymat et la sécurité des données sensibles.', 'FR'),
(144, 'configuration_tips', 'You are on the page of the general configuration of your server. If you indicate the authentication information of your FTP or SFTP server, the website can propose an advanced configuration of your server respecting the anonymity and the security of sensitive data.', 'EN'),
(145, 'configuration_storage_title', 'Stockage distant', 'FR'),
(146, 'configuration_storage_title', 'Remote storage', 'EN'),
(147, 'configuration_storage_address', 'Adresse IP & Port', 'FR'),
(148, 'configuration_storage_address', 'IP address & Port', 'EN'),
(149, 'configuration_storage_protocol', 'Type de protocole', 'FR'),
(150, 'configuration_storage_protocol', 'Protocol type', 'EN'),
(151, 'configuration_storage_ftp', 'Protocole de Transfert de Fichier (FTP)', 'FR'),
(152, 'configuration_storage_ftp', 'File Transfer Protocol (FTP)', 'EN'),
(153, 'configuration_storage_sftp', 'Protocole de Transfert de Fichier par SSH (SFTP)', 'FR'),
(154, 'configuration_storage_sftp', 'SSH File Transfer Protocol (SFTP)', 'EN'),
(155, 'configuration_storage_username', 'Nom d\'utilisateur', 'FR'),
(156, 'configuration_storage_username', 'Username', 'EN'),
(157, 'configuration_storage_password', 'Mot de passe', 'FR'),
(158, 'configuration_storage_password', 'Password', 'EN'),
(159, 'configuration_settings_title', 'Paramètres', 'FR'),
(160, 'configuration_settings_title', 'Settings', 'EN'),
(161, 'actions_description', 'La page des actions est une page qui permet l\'envoi d\'actions rapides et personnalisées. La présence d\'interrupteurs permet d\'activer ou de désactiver certaines fonctionnalités du serveur. Enfin, les commandes personnalisées permettent de modifier le comportement général du serveur.', 'FR'),
(162, 'actions_description', 'The actions page is a page that allows you to send quick and personalized actions. The presence of switches allows you to activate or deactivate certain server features. Finally, the custom commands allow to modify the general behavior of the server.', 'EN'),
(165, 'actions_remaining', '*%d restante(s)*', 'FR'),
(166, 'actions_remaining', '*%d remaining*', 'EN'),
(167, 'actions_actions_title', 'Actions', 'FR'),
(168, 'actions_commands_title', 'Commandes', 'FR'),
(169, 'actions_commands_title', 'Commands', 'EN'),
(170, 'actions_add_command', 'Ajouter une commande...', 'FR'),
(171, 'actions_add_command', 'Add a command...', 'EN'),
(172, 'actions_commands_remove', 'Supprimer', 'FR'),
(173, 'actions_commands_remove', 'Remove', 'EN'),
(174, 'actions_commands_execute', 'Exécuter', 'FR'),
(175, 'actions_commands_execute', 'Execute', 'EN'),
(176, 'actions_tips', 'Vous êtes sur la page des actions. Vous pourrez retrouver ici toutes les actions, interrupteurs et commandes disponibles qui peuvent être exécutés à distance sur votre serveur.', 'FR'),
(177, 'actions_tips', 'You are on the actions page. Here you can find all available actions, switches and commands that can be executed remotely on your server.', 'EN'),
(178, 'console_tips', 'Vous êtes sur la page de la console interactive. Vous pourrez retrouver ici la visualisation en temps réel de ce qu\'il se passe sur votre serveur. De plus, vous avez un certain libre arbitre pour envoyer des requêtes quelconque à votre serveur.', 'FR'),
(179, 'console_tips', 'You are on the interactive console page. Here you can see in real time what is happening on your server. Moreover, you have some freedom to send any request to your server.', 'EN'),
(180, 'console_description', 'La console interactive est une page qui permet de visualiser les informations de sortie du serveur en temps réel. Ces informations sont directement issues du programme d\'exécution et sont indépendantes du site. De plus, la page permet de saisir des commandes libres vers le serveur.', 'FR'),
(181, 'console_description', 'The interactive console is a page that allows you to view the server\'s output information in real time. This information comes directly from the execution program and is independent of the website. In addition, the page allows you to enter free commands to the server.', 'EN'),
(182, 'console_controller_title', 'Contrôleur principal', 'FR'),
(183, 'console_controller_title', 'Main controller', 'EN'),
(184, 'console_terminal_title', 'Terminal d\'observation', 'FR'),
(185, 'console_terminal_title', 'Monitoring terminal', 'EN'),
(186, 'console_controller_placeholder', 'Écrivez quelque chose...', 'FR'),
(187, 'console_controller_placeholder', 'Write something...', 'EN'),
(188, 'tasks_tips', 'Vous êtes sur la page des tâches planifiées. Vous pourrez retrouver ici la liste de toutes les tâches planifiées prévues ou terminées. Les tâches sont automatiquement gérer par l\'infrastructure du site tant que vos informations de connexion sont valides.', 'FR'),
(189, 'tasks_tips', 'You are on the scheduled tasks page. Here you can find the list of all scheduled or completed tasks. The tasks are automatically managed by the website infrastructure as long as your credentials are valid.', 'EN'),
(190, 'tasks_description', 'La page des tâches planifiées est une tâche fonctionnant de manière semi-automatique. La page propose la création d\'actions qui peuvent être exécutées plus tard et de manière répétitives dans le temps. Une fois enregistrées, ces tâches peuvent être supprimées mais sont exécutées automatiquement à votre serveur à la date du déclenchement.', 'FR'),
(191, 'tasks_description', 'The scheduled tasks page is a semi-automatically working task. The page offers the creation of actions that can be executed later and repeatedly over time. Once saved, these tasks can be deleted but are automatically executed on your server at the date of the trigger.', 'EN'),
(192, 'tasks_server_label', 'Serveur concerné', 'FR'),
(193, 'tasks_server_label', 'Related server', 'EN'),
(194, 'tasks_date_label', 'Date de déclenchement', 'FR'),
(195, 'tasks_date_label', 'Trigger date', 'EN'),
(196, 'tasks_action_label', 'Action de réalisation', 'FR'),
(197, 'tasks_action_label', 'Action plan', 'EN'),
(198, 'form_contact_success', 'Le message à été envoyé avec succès. Vous recevrez une réponse dans les plus brefs délais.', 'FR'),
(199, 'form_contact_success', 'The message has been sent successfully. You will receive a reply as soon as possible.', 'EN'),
(200, 'form_contact_failed', 'Une erreur interne s\'est produite lors de l\'envoi de votre message au site web. Réponse : « $1 ».', 'FR'),
(201, 'form_contact_failed', 'An internal error occurred when sending your message to the website. Response: \"$1\".', 'EN'),
(202, 'form_signup_failed', 'Une erreur interne s\'est produite lors de votre inscription au site. Réponse : « $1 ».', 'FR'),
(203, 'form_signup_failed', 'An internal error occurred when you registered on the website. Response: \"$1\".', 'EN'),
(204, 'form_signup_success', 'Votre compte utilisateur a été créé avec succès. Vous allez être redirigé automatiquement dans quelques secondes.', 'FR'),
(205, 'form_signup_success', 'Your user account has been successfully created. You will be redirected automatically in a few seconds.', 'EN'),
(206, 'form_signup_duplication', 'Ce nom d\'utilisateur est déjà utilisé. Veuillez en choisir un autre.', 'FR'),
(207, 'form_signup_duplication', 'This username is already used. Please choose another one.', 'EN'),
(208, 'form_signin_success', 'Vous êtes désormais connecté à votre compte utilisateur. Vous allez être redirigé automatiquement dans quelques secondes.', 'FR'),
(209, 'form_signin_success', 'You are now connected to your user account. You will be redirected automatically in a few seconds.', 'EN'),
(210, 'form_signin_failed', 'Une erreur interne s\'est produite lors de votre connexion au compte utilisateur. Réponse : « $1 ».', 'FR'),
(211, 'form_signin_failed', 'An internal error occurred while connecting to your user account. Response: \"$1\".', 'EN'),
(212, 'form_signin_invalid', 'Les identifiants de connexion renseignés sont invalides ou n\'existent pas.', 'FR'),
(213, 'form_signin_invalid', 'The provided credentials are invalid or do not exist.', 'EN'),
(214, 'form_signin_recover', 'Si le nom d\'utilisateur renseigné est valide, alors le nouveau mot de passe a bien été mis à jour.', 'FR'),
(215, 'form_signin_recover', 'If the provided username is valid, then the new password has been updated.', 'EN'),
(216, 'form_signup_onetime', 'Votre accès unique a été créé avec succès. Vous conserverez son accès tout au long de votre session. Une fois terminée, vous devrez de nouveau recréer un accès. Attention ce mode possède des fonctionnalités restreintes.', 'FR'),
(217, 'form_signup_onetime', 'Your one-time access has been successfully created. You will be able to keep this access during your session. Once expired, you will have to recreate an access again. Please note that this method has limited features.', 'EN'),
(218, 'form_contact_mailing', 'Votre message d\'assistance à bien été envoyé. Vous receverez une réponse sous 24 heures.\r\n\r\nVoici le récapitulatif de votre message :\r\n\r\n%s', 'FR'),
(219, 'form_contact_mailing', 'Your message for support has been sent. You will receive a reply within 24 hours.\r\n\r\nHere is the summary of your message:\r\n\r\n%s', 'EN'),
(222, 'index_feature_title_2', 'Performant', 'FR'),
(223, 'index_feature_title_2', 'Powerful', 'EN'),
(224, 'index_feature_title_3', 'Moderne', 'FR'),
(225, 'index_feature_title_3', 'Modern', 'EN'),
(226, 'index_feature_title_4', 'Sécurisé', 'FR'),
(227, 'index_feature_title_4', 'Secure', 'EN'),
(228, 'index_feature_description_4', 'Grâce à l\'utilisation de moyens modernes et performances des outils de communications RCON, la sécurisation et le chiffrement de bout en bout des données transmises et reçues par le site est maintenant possible. Toutes les informations sensibles que vous pourriez peut-être renseignées seront automatiquement chiffré au travers d\'algorithme de type de militaire. Nous ne stockons jamais en clair les mots de passes, jamais.', 'FR'),
(229, 'index_feature_description_4', 'Thanks to the use of modern, high-performance RCON communications tools, end-to-end security and encryption of data transmitted and received by the website is now possible. Any sensitive information you may enter will be automatically encrypted using a military-grade algorithm. We never store passwords in clear text, never.', 'EN'),
(230, 'statistics_axis_utc', 'Heure UTC', 'FR'),
(231, 'statistics_axis_utc', 'UTC time', 'EN'),
(232, 'statistics_axis_players', 'Nombre de joueurs', 'FR'),
(233, 'statistics_axis_players', 'Player count', 'EN'),
(234, 'statistics_axis_cpu', 'Processeur (en %)', 'FR'),
(235, 'statistics_axis_cpu', 'CPU (in %)', 'EN'),
(236, 'statistics_axis_ram', 'Mémoire vive (en %)', 'FR'),
(237, 'statistics_axis_ram', 'RAM (in %)', 'EN'),
(238, 'statistics_axis_usage', 'Pourcentage d\'utilisation', 'FR'),
(239, 'statistics_axis_usage', 'Usage percentage', 'EN'),
(240, 'dashboard_players', 'Liste des joueurs', 'FR'),
(241, 'dashboard_players', 'Players list', 'EN'),
(242, 'dashboard_state_info', 'Informations', 'FR'),
(243, 'dashboard_state_map', 'Carte actuelle', 'FR'),
(244, 'dashboard_state_map', 'Current map', 'EN'),
(245, 'dashboard_state_players', 'Nombre de joueurs', 'FR'),
(246, 'dashboard_state_players', 'Player count', 'EN'),
(247, 'dashboard_state_nodata', 'Aucun signal\r\n(Inconnu)', 'FR'),
(248, 'dashboard_state_nodata', 'No signal\r\n(Unknown)', 'EN'),
(249, 'dashboard_edit_client_address', 'Saisissez l\'adresse IP du serveur.\\nLaissez vide pour aucun changement.', 'FR'),
(250, 'dashboard_edit_client_address', 'Type in the IP address of the server.\\nLeave blank for no change.', 'EN'),
(251, 'dashboard_edit_client_port', 'Saisissez le port de communication du serveur.\\nLaissez vide pour aucun changement.', 'FR'),
(252, 'dashboard_edit_client_port', 'Type the communication port of the server.\\nLeave blank for no change.', 'EN'),
(253, 'dashboard_edit_admin_address', 'Saisissez l\'adresse IP du module d\'administration.\\nLaissez vide pour aucun changement.', 'FR'),
(254, 'dashboard_edit_admin_address', 'Type in the IP address of the administration module.\\nLeave blank for no change.', 'EN'),
(255, 'dashboard_edit_admin_port', 'Saisissez le port de communication du module d\'administration.\\nLaissez vide pour aucun changement.', 'FR'),
(256, 'dashboard_edit_admin_port', 'Type in the communication port of the administration module.\\nLeave blank for no change.', 'EN'),
(257, 'dashboard_edit_admin_password', 'Saisissez le mot de passe du module d\'administration.\\nLaissez vide pour aucun changement.', 'FR'),
(258, 'dashboard_edit_admin_password', 'Type in the password for the administration module.\\nLeave blank for no change.', 'EN'),
(259, 'dashboard_state_running', 'En fonctionnement\r\n($1)', 'FR'),
(260, 'dashboard_state_running', 'Operating\r\n($1)', 'EN'),
(261, 'dashboard_state_service', 'En maintenance\r\n($1)', 'FR'),
(262, 'dashboard_state_service', 'In servicing\r\n($1)', 'EN'),
(263, 'global_error_fatal', 'Une erreur interne s\'est produite lors de la récupération des informations du serveur. Veuillez rafraîchir la page pour relancer la surveillance. Message : « $1 ».', 'FR'),
(264, 'global_error_fatal', 'An internal error occurred while retrieving information from the server. Please refresh the page to restart the monitoring. Message: « $1 ».', 'EN'),
(265, 'global_service_title', 'Service', 'EN'),
(266, 'global_action_success', 'La requête demandée a été envoyée avec succès avec le serveur distant. Dans certains cas, il peut se produire des effets secondaires en fonction de la configuration de votre serveur.', 'FR'),
(267, 'global_action_success', 'The desired request has been successfully sent to the remote server. In some cases, there may be side effects depending on your server configuration.', 'EN'),
(276, 'legal_tips', 'Vous êtes sur la page des mentions légales du site. Vous pouvez retrouver toutes les informations juridiques disponibles en utilisant ce site Internet. Elles sont uniquement disponibles en français.', 'FR'),
(277, 'legal_tips', 'You are on the legal notice page of the website. You can find all the legal information available by using this website. They are only available in French.', 'EN'),
(278, 'dashboard_edit_remove', 'Voulez-vous supprimer ce serveur ?', 'FR'),
(279, 'dashboard_edit_remove', 'Do you want to delete this server?', 'EN'),
(280, 'actions_switch_flashlight', 'Autoriser la lampe torche', 'FR'),
(281, 'actions_switch_flashlight', 'Lock the server', 'EN'),
(282, 'actions_switch_cheats', 'Autoriser la triche', 'FR'),
(283, 'actions_switch_cheats', 'Allow cheating', 'EN'),
(284, 'actions_switch_voice', 'Autoriser les voix par IP', 'FR'),
(285, 'actions_switch_voice', 'Allow the flashlight', 'EN'),
(286, 'actions_commands_value', 'Entrez la valeur pour exécuter la commande.', 'FR'),
(287, 'actions_commands_value', 'Type a value to execute the command.', 'EN'),
(288, 'actions_commands_level', 'Changer la carte actuelle', 'FR'),
(289, 'actions_commands_level', 'Change the current map', 'EN'),
(290, 'actions_commands_password', 'Modifier le mot de passe', 'FR'),
(291, 'actions_commands_password', 'Change the password', 'EN'),
(292, 'actions_commands_gravity', 'Modifier le niveau de gravité', 'FR'),
(293, '', 'Change the gravity level', 'EN'),
(294, 'actions_add_name', 'Entrez l\'intitulé de la nouvelle commande personnalisée.', 'FR'),
(295, 'actions_add_name', 'Enter the label of the new custom command.', 'EN'),
(296, 'actions_add_content', 'Entrez le contenu de la commande personnalisée.', 'FR'),
(297, 'actions_add_content', 'Enter the content of the custom command.', 'EN'),
(298, 'tasks_added', 'La tâche planifiée pour ce serveur a été créée avec succès. La page va être rafraîchie automatiquement.', 'FR'),
(299, 'tasks_added', 'The scheduled task for this server has been successfully created. The page will be refreshed automatically.', 'EN'),
(300, 'tasks_removed', 'La tâche planifiée pour ce serveur a été supprimée avec succès. La page va être rafraîchie automatiquement.', 'FR'),
(301, 'tasks_removed', 'The scheduled task for this server has been successfully deleted. The page will be refreshed automatically.', 'EN'),
(302, 'tasks_edit_remove', 'Voulez-vous supprimer cette tâche ?', 'FR'),
(303, 'tasks_edit_remove', 'Do you want to delete this task?', 'EN'),
(304, 'header_subtitle_user', 'Compte utilisateur', 'FR'),
(305, 'header_subtitle_user', 'User account', 'EN'),
(306, 'header_subtitle_admin', 'Administration', 'FR'),
(307, 'help_tips', 'Vous êtes sur la page de l\'assistance utilisateur. Vous pourrez retrouver ici toutes les informations concernant la résolution des potentiels problèmes que vous pouvez rencontrer lors de l\'utilisation du site. De plus, vous retrouverez des informations utiles sur le fonctionnement général et comment améliorer votre confort de navigation.', 'FR'),
(308, 'help_tips', 'You are on the user support page. Here you can find all the information concerning the resolution of potential problems that you may encounter while using the website. In addition, you will find useful information about the general operation and how to improve your browsing comfort.', 'EN'),
(309, 'help_description', 'La page de l\'assistance utilisateur est la page la plus important pour tous les utilisateurs qui peuvent rencontrer des problèmes lors de l\'utilisation ou la navigation à travers l\'entièreté du site. Elle permet également de donner des indications supplémentaires afin d\'améliorer l\'expérience générale d\'utilisation.', 'FR'),
(310, 'help_description', 'The user support page is the most important page for all users who may encounter problems while using or navigating through the entire website. It also provides additional guidance to improve the overall user experience.', 'EN'),
(311, 'help_faq', 'Foire aux questions', 'FR'),
(312, 'help_faq', 'Frequently asked questions', 'EN'),
(313, 'help_question_1', 'Comment s\'inscrire sur le site ?', 'FR'),
(314, 'help_question_1', 'How do I register on the website?', 'EN'),
(315, 'help_question_2', 'Quels sont les avantages des utilisateurs donateurs ?', 'FR'),
(316, 'help_question_2', 'What are the benefits for donator users?', 'EN'),
(317, 'help_question_3', 'Quelle est la différence entre le site actuel et les concurrents ?', 'FR'),
(318, 'help_question_3', 'What is the difference between the current website and the competitors?', 'EN'),
(319, 'help_donators_count', 'Donateurs', 'FR'),
(320, 'help_donators_count', 'Donators', 'EN'),
(321, 'help_servers_count', 'Serveurs', 'FR'),
(322, 'help_servers_count', 'Servers', 'EN'),
(323, 'help_users_count', 'Utilisateurs', 'FR'),
(324, 'help_users_count', 'Users', 'EN'),
(325, 'help_requests_count', 'Requêtes', 'FR'),
(326, 'help_requests_count', 'Requests', 'EN'),
(327, 'help_answer_1', 'L\'inscription sur le site se fait sur la page d\'accueil du site. Sur cette page, vous retrouverez la présentation des fonctionnalités ainsi qu\'un moyen afin de procéder à votre inscription et à votre connexion. L\'inscription se fait en deux étapes : d\'abord vous devez renseigner vos informations personnelles afin de créer un compte utilisateurs, dans un second temps vous serez invité à remplir des informations sur l\'un de vos serveurs de jeux. Ces informations seront ensuite traités pour l\'affichage des données sur le tableau de bord.', 'FR'),
(328, 'help_answer_1', 'Registration on the website is done on the home page of the website. On this page, you will find the presentation of the functionalities as well as a way to proceed to your registration and connection. Registration is done in two steps: first you have to fill in your personal information in order to create a user account, then you will be asked to fill in information about one of your game servers. This information will then be processed for display on the dashboard.', 'EN'),
(329, 'help_answer_2', 'Lors de votre inscription au site, votre compte est automatiquement défini en tant qu\'utilisateur classique et vous possédez d\'or et déjà d\'un grands nombres de fonctionnalités disponibles. Cependant, si vous réalisez une donation unique d\'un montant que vous voulez, vous aurez la possibilité de débloquer des avantages et des fonctionnalités supplémentairs pour améliorer votre expérience d\'utilisateurs.', 'FR'),
(330, 'help_answer_2', 'When you register on the website, your account is automatically set up as a regular user and you already have a number of features available to you. However, if you make a one-off donation of any amount you wish, you will be able to unlock additional benefits and features to enhance your user experience.', 'EN'),
(331, 'help_answer_3', 'Le site actuel et les sites concurrents sont possèdent plusieurs différences. D\'abord, les sites concurrents ne peuvent pas être accessibles sans compte utilisateur et sans avoir déjà acheté un service chez leurs infrastructures. Cela signifie que l\'accès à leur panneau d\'administration dépend d\'une souscription à leurs services, notre site est alors totalement gratuit d\'utilisateur. La seconde est que notre site utilise les dernières versions de communication entre nos serveurs et vos infrastructures afin de faciliter la rapidité d\'exécution de toutes vos actions.', 'FR'),
(332, 'help_answer_3', 'The current site and the competing sites have several differences. Firstly, the competing sites cannot be accessed without a user account and without having already purchased a service from their infrastructure. This means that access to their administration panel depends on a subscription to their services, so our site is completely free of charge. The second is that our site uses the latest versions of communication between our servers and your infrastructure to facilitate the speed of execution of all your actions.', 'EN'),
(345, 'configuration_hostname_title', 'Nom d\'affichage', 'FR'),
(346, 'configuration_hostname_title', 'Display name', 'EN'),
(347, 'configuration_hostname_description', 'Ce paramètre permet de changer le nom du serveur affiché sur la liste des serveurs mondiaux de la communauté Steam.', 'FR'),
(348, 'configuration_hostname_description', 'This setting allows you to change the name of the server displayed on the list of global servers in the Steam community.', 'EN'),
(349, 'configuration_rcon_title', 'Mot de passe d\'administration', 'FR'),
(350, 'configuration_rcon_title', 'Administration password', 'EN'),
(351, 'configuration_rcon_description', 'Ce paramètre permet de changer le mot de passe vers le module d\'administration RCON pour le contrôle à distance.', 'FR'),
(352, 'configuration_rcon_description', 'This parameter is used to change the password to the RCON administration module for remote control.', 'EN'),
(353, 'configuration_loading_title', 'Écran de chargement', 'FR'),
(354, 'configuration_loading_title', 'Loading screen', 'EN'),
(355, 'configuration_loading_description', 'Ce paramètre permet de modifier l\'URL de destination vers l\'écran de chargement qui doit être affiché lors du téléchargement automtique des ressources.', 'FR'),
(356, 'configuration_loading_description', 'This parameter allows you to change the destination URL to the loading screen that should be displayed when automatically downloading resources.', 'EN'),
(357, 'user_description', 'La page du compte utilisateur est une page où les utilisateurs authentifiés peuvent modifier leur nom d\'utilisateur ainsi que leur mot de passe afin d\'accéder à leur compte. De plus, l\'utilisateur peut téléverser une photo de profil afin d\'être reconnaissable sur les services de messageries internes au site.', 'FR'),
(358, 'user_description', 'The user account page is a page where authenticated users can change their username and password to access their account. In addition, the user can upload a profile picture in order to be recognizable on the website\'s internal messaging services.', 'EN'),
(359, 'user_tips', 'Vous êtes sur la page du compte utilisateur. Vous pourrez retrouver la possibilité de modifier vos informations de connexion personnelles ainsi que le moyen d\'ajouter un nouveau serveur à votre compte.', 'FR'),
(360, 'user_tips', 'You are on the user account page. You will find the possibility to modify your personal login credentials and the way to add a new server to your account.', 'EN'),
(361, 'user_account', 'Informations du compte', 'FR'),
(362, 'user_account', 'Account details', 'EN'),
(363, 'user_actions', 'Actions du compte', 'FR'),
(364, 'user_actions', 'Account actions', 'EN'),
(365, 'user_reconnect', 'Se reconnecter', 'FR'),
(366, 'user_reconnect', 'Reconnect', 'EN'),
(367, 'user_disconnect', 'Se déconnecter', 'FR'),
(368, 'user_disconnect', 'Disconnect', 'EN'),
(369, 'user_reconnected', 'Vous avez été reconnecté à votre compte utilisateur. Vous allez être redirigé automatiquement dans quelques secondes.', 'FR'),
(370, 'user_reconnected', 'You have been reconnected to your user account. You will be redirected automatically in a few seconds.', 'EN'),
(371, 'user_disconnected', 'Vous êtes désormais déconnecté de votre compte utilisateur. Vous allez être redirigé automatiquement dans quelques secondes.', 'FR'),
(372, 'user_disconnected', 'You are now disconnected from your user account. You will be redirected automatically in a few seconds.', 'EN'),
(373, 'user_updated', 'Les informations personnelles de votre compte ont bien été mises à jour dans la base de données.', 'FR'),
(374, 'user_updated', 'Your personal account information has been updated in the database.', 'EN'),
(375, 'user_insert', 'Votre nouveau serveur a bien été ajouté dans la base de données. Il est désormais disponible à la sélection sur le tableau de bord.', 'FR'),
(376, 'user_insert', 'Your new server has been added to the database. It is now available for selection on the dashboard.', 'EN'),
(377, 'global_remove_title', 'Supprimer', 'FR'),
(378, 'global_remove_title', 'Remove', 'EN'),
(379, 'user_edit_remove', 'Vous voulez supprimer votre compte utilisateur ? Attention, la suppression est définitive et irréversible.', 'FR'),
(380, 'user_edit_remove', 'You want to delete your user account? Please note that the deletion is final and irreversible.', 'EN'),
(381, 'user_removed', 'Votre compte utilisateur a été supprimé avec succès. Veuillez rafraîchir la page pour être redirigé vers la page d\'accueil.', 'FR'),
(382, 'user_removed', 'Your user account has been successfully deleted. Please refresh the page to be redirected to the home page.', 'EN');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `client_id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` char(60) NOT NULL,
  `access_token` char(64) DEFAULT NULL,
  `creation_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `level` enum('standard','donator','admin') NOT NULL DEFAULT 'standard',
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
