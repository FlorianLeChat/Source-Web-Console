<?php
	//
	// Contrôleur de gestion de la page de l'assistance utilisateur.
	//

	// On récupère d'abord toutes les valeurs nécessaires pour l'inclusion
	//	dans le moteur TWIG.
	$donators = $server->connector->query("SELECT COUNT(*) FROM `users` WHERE `level` = 'donator';")->fetch();
	$servers = $server->connector->query("SELECT COUNT(*) FROM `servers`;")->fetch();
	$users = $server->connector->query("SELECT COUNT(*) FROM `users`;")->fetch();
	$requests = $server->connector->query("SELECT COUNT(*) FROM `logs`;")->fetch();

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Nombre d'utilisateurs donateurs.
		"help_donators_count" => $donators["COUNT(*)"],

		// Nombre de serveurs enregistrés.
		"help_servers_count" => $servers["COUNT(*)"],

		// Nombre de comptes utilisateurs.
		"help_users_count" => $users["COUNT(*)"],

		// Nombre de requêtes réalisées.
		"help_requests_count" => $requests["COUNT(*)"]

	];
?>