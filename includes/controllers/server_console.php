<?php
	//
	// Contrôleur de visualisation de la console interactive.
	// 	Source : https://forums.nfoservers.com/viewtopic.php?t=10090
	//

	// Inclusion du contrôleur principal.
	require_once(__DIR__ . "/../controller.php");

	// Adresse IP/port de destination pour les paquets UDP.
	$web_host = "51.75.125.244";
	$web_port = 27026;

	// Adresse IP/port de destination du serveur cible.
	$remote_ip = "51.75.125.244";
	$remote_port = 27015;

	// Création du socket de connexion.
	if (!($socket = socket_create(AF_INET, SOCK_DGRAM, 0)))
	{
		echo("Échec de création du socket." . PHP_EOL);
		exit();
	}

	// Inclusion des options de connexion.
	if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))
	{
		echo("Échec de définition des sockets" . PHP_EOL);
		exit();
	}

	// Définition de la combinaison adresse IP/port de destination.
	if (!socket_bind($socket, $web_host, $web_port))
	{
		echo("Échec de définition de l'adresse réseau." . PHP_EOL);
		exit();
	}

	echo("Tentative de connexion." . PHP_EOL);

	// Envoi d'un premier message pour valider la connexion distante.
	$message = "Test";
	$length = mb_strlen($message);

	socket_sendto($socket, $message, $length, 0, $remote_ip, $remote_port);

	// Récupération des données en mémoire de récupération.
	while(1)
	{
		$receive = socket_recvfrom($socket, $buffer, 512, 0, $remote_ip, $remote_port);

		// Suppression des caractères bizarres au début du buffer.
		$buffer = substr($buffer, 7);

		// Suppression des caractères d'échappement.
		$buffer = str_replace(["\0", "\r"], "", $buffer);

		echo($buffer . PHP_EOL);
	}

	// Fermeture du socket de connexion.
	socket_close($socket);
?>