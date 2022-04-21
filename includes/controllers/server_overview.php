<?php
	//
	// Contrôleur de gestion des données de l'instance.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement connecté
	//	à un compte utilisateur.
	if (empty($_SESSION["identifier"]))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		header("Location: ?target=dashboard");
		exit();
	}

	// On vérifie si l'utilisateur ne tente pas de dépasser
	//	le temps d'attente nécessaire entre chaque requête
	//	de rafraîchissement.
	if (empty($_SESSION["overview_cooldown"]))
	{
		// Le temps d'attente est calé sur celui indiqué du
		//	côté JavaScript.
		$_SESSION["overview_cooldown"] = time() + 5;
	}
	else
	{
		if ($_SESSION["overview_cooldown"] > time())
		{
			// Indication : « Too Many Requests ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/429
			http_response_code(429);
			exit();
		}
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on récupère les données transmises dans la requête
		//	avant de vérifier leur validité.
		$password = $_POST["server_password"] ?? "";
		$address = $_POST["server_address"] ?? "";
		$port = $_POST["server_port"] ?? "";

		if (empty($address) || empty($port))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		try
		{
			// On tente après d'établir une connexion avec l'instance.
			$server->connectInstance($address, $port, $password);

			// En cas de réussite, on récupère toutes les informations
			//	disponibles et fournies par le module d'administration.
			$info = $server->query->GetInfo();

			// On encode alors certaines de ces informations pour les
			//	transmettre au client à travers le JavaScript.
			echo(json_encode([

				// Mode de jeu.
				"gamemode" => $info["ModDesc"],

				// Carte/environnement.
				"maps" => $info["Map"],

				// Nombre de robots.
				"bots" => $info["Bots"],

				// Sécurisation par mot de passe.
				"password" => $info["Password"],

				// Nombre de joueurs/clients.
				"players" => $info["Players"],

				// Nombre maximum de joueurs/clients.
				"max_players" => $info["MaxPlayers"],

				// Liste des joueurs
				"players_list" => $server->query->GetPlayers()

			]));
		}
		catch (Exception $error)
		{
			// Si une exception est lancé, on encode alors le message
			//	d'erreur sous format JSON.
			echo(json_encode([

				"error" => $error->getMessage()

			]));
		}
		finally
		{
			// Si tout se passe bien, on libère le socket réseau pour
			//	d'autres scripts du site.
			$server->query->Disconnect();
		}

		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>