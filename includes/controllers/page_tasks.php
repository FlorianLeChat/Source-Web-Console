<?php
	//
	// Contrôleur de gestion de la page des tâches planifiées.
	//

	// On récupère tout d'abord les identifiants uniques concernant
	//	l'utilisateur et le serveur actuellement sélectionné.
	$user_id = $_SESSION["user_id"];

	// On implémente ensuite une fonction TWIG afin de déterminer le
	//	nom complet du jeu actuellement utilisé par le serveur.
	$function = new \Twig\TwigFunction("getNameByGameID", function(int $identifier, string $fallback) use ($server)
	{
		return $server->getNameByGameID($identifier, $fallback);
	});

	$twig->addFunction($function);

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Liste des tâches planifiées prévues.
		"tasks_list" => $server->getScheduledTasks($user_id),

		// Liste des serveurs depuis la base de données.
		"tasks_servers" => $server->getServersData($user_id)

	];
?>