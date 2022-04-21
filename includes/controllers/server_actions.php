<?php
	//
	// Contrôleur de gestion des actions et commandes de l'instance.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement connecté
	//	à un compte utilisateur.
	if (empty($_SESSION["user_id"]))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		header("Location: ?target=dashboard");
		exit();
	}

	// On vérifie si l'utilisateur ne tente pas de dépasser
	//	le temps d'attente nécessaire entre chaque requête
	//	d'actions/commandes.
	if (empty($_SESSION["actions_cooldown"]))
	{
		$_SESSION["actions_cooldown"] = time() + 1;
	}
	else
	{
		if ($_SESSION["actions_cooldown"] > time())
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
		// Si c'est le cas, on tente de récupérer l'instance sélectionnée
		// 	ainsi que la requête demandée  via les données transmises dans la requête.
		$action = $_POST["server_action"] ?? "";
		$instance = $server->getInstance($_SESSION["user_id"], $_POST["server_id"] ?? 0);

		if (empty($action) || empty($instance))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		try
		{
			// On tente après d'établir une connexion avec l'instance.
			$server->connectInstance($instance["admin_address"] ?? $instance["client_address"], $instance["admin_port"] ?? $instance["client_port"], $server->password_decrypt($instance["admin_password"] ?? ""));

			// On envoie ensuite la requête correspondante à l'instance.
			switch ($action)
			{
				case "shutdown":
				{
					// Requête d'arrêt classique
					//	Note : l'instance peut automatiquement redémarrer après
					//		un certain temps sur certains jeux.
					$server->query->Rcon("shutdown");
					break;
				}

				case "force":
				{
					// Requête d'arrêt forcé
					//	Note : doit être seulement utilisé lorsque l'instance ne
					//		répond plus à cause des risques de pertes de données.
					$server->query->Rcon("quit");
					break;
				}

				case "restart":
				{
					// Requête de redémarrage.
					$server->query->Rcon("_restart");
					break;
				}

				case "update":
				{
					// Requête de mise à jour.
					//	Note : uniquement supportée sur les versions récentes
					//		du protocole RCON.
					$server->query->Rcon("svc_update");
					break;
				}

				case "service":
				{
					// Mise en maintenance : verouillage de l'instance.
					$server->query->Rcon("sv_password \"password\"");
					break;
				}
			}

			// On enregistre par la même occasion l'action en historique.
			$server->addActionLogs($instance["server_id"], $action);

			// On affiche ensuite le message de validation.
			echo($translation->getPhrase("dashboard_action_$action"));
		}
		catch (Exception $error)
		{
			// Si une exception est lancé, on affiche l'erreur.
			echo($error->getMessage());
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