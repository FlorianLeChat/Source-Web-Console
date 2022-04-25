<?php
	//
	// Contrôleur de gestion de la page de la configuration.
	//

	// On récupère tout d'abord l'idenfiant unique de l'utilisateur
	$user_id = $_SESSION["user_id"] ?? 0;

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Identifiants du serveur de stockage.
		"configuration_credentials" => $server->getExternalStorage($user_id, $server_id)

	];
?>