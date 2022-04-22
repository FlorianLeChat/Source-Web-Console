<?php
	//
	// Contrôleur de gestion des actions et commandes du serveur.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement connecté
	//	à un compte utilisateur.
	$user_id = $_SESSION["user_id"];

	if (empty($user_id))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
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
		// Si c'est le cas, on tente de récupérer le serveur sélectionné ainsi
		// 	que la requête demandée via les données transmises dans la requête.
		$action = $_POST["server_action"] ?? "";
		$remote = $server->getServerData($user_id, $_SESSION["server_id"] ?? 0);

		if (empty($action) || empty($remote))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		// On récupère par la même occasion certaines informations pour
		//	l'exécution des commandes personnalisées.
		$value = $_POST["server_value"] ?? "";
		$commands = $server->getCustomCommands($user_id);

		switch ($value)
		{
			case "#ADD#":
			{
				// Ajout d'une commande personnalisée.
				//	Note : on doit séparer le nom de la commande de son
				//		contenu qui a été créé du côté JavaScript.
				$arguments = explode("|", $action);
				$server->addCustomCommand($user_id, $arguments[0] ?? "???", $arguments[1] ?? "say");
				exit();
			}

			case "#REMOVE#":
			{
				// Suppression d'une commande personnalisée.
				$server->removeCustomCommand($user_id, $action);
				exit();
			}
		}

		try
		{
			// On tente après d'établir une connexion avec le serveur.
			$server->connectServer($remote["admin_address"] ?? $remote["client_address"], $remote["admin_port"] ?? $remote["client_port"], $remote["admin_password"] ?? "");

			// On envoie ensuite la requête correspondante au serveur.
			switch ($action)
			{
				case "shutdown":
				{
					// Action : requête d'arrêt classique.
					//	Note : le serveur peut automatiquement redémarrer après
					//		un certain temps sur certains jeux.
					$server->query->Rcon("sv_shutdown");
					break;
				}

				case "force":
				{
					// Action : requête d'arrêt forcé.
					//	Note : doit être seulement utilisé lorsque le serveur ne
					//		répond plus à cause des risques de pertes de données.
					$server->query->Rcon("quit");
					break;
				}

				case "restart":
				{
					// Action : requête de redémarrage.
					$server->query->Rcon("_restart");
					break;
				}

				case "update":
				{
					// Action : requête de mise à jour.
					//	Note : uniquement supportée sur les versions récentes
					//		du protocole RCON.
					$server->query->Rcon("svc_update");
					break;
				}

				case "service":
				{
					// Action : mise en maintenance - verouillage du serveur.
					$server->query->Rcon("sv_password \"password\"");
					break;
				}

				case "flashlight":
				{
					// Interrupteur : utilisation de la lampe torche.
					$server->query->Rcon("toggle mp_flashlight");
					break;
				}

				case "cheats":
				{
					// Interrupteur : utilisation de logiciels de triche.
					$server->query->Rcon("toggle sv_cheats");
					break;
				}

				case "voice":
				{
					// Interrupteur : utilisation des communications vocales.
					$server->query->Rcon("toggle sv_voiceenable");
					break;
				}

				case "level":
				{
					// Commande : changement de l'environnement/carte.
					$server->query->Rcon("changelevel \"$value\"");
					break;
				}

				case "password":
				{
					// Commande : changement du mot de passe d'accès.
					$server->query->Rcon("sv_password \"$value\"");
					break;
				}

				case "gravity":
				{
					// Commande : changement de la gravité.
					$server->query->Rcon("sv_gravity \"$value\"");
					break;
				}

				default:
				{
					// Si aucune action est trouvée, alors il s'agit probablement
					//	d'une commande personnalisée. On tente alors de récupérer
					//	les données de toutes les commandes pour trouver celle
					//	qui semble avoir été choisie par l'uitlisateur.
					$command = array_filter($commands, function(array $command)
					{
						global $action;
						return $command["command_id"] == $action;
					});

					// On vérifie si notre recherche a trouvée une commande
					//	personnalisée dans la base de données.
					if (!empty($command))
					{
						// Récupération du premier résultat.
						$command = array_shift($command);

						// Définition du nom de l'action.
						$action = $command["name"];

						// Exécution de la commande avec la valeur.
						$server->query->Rcon($command["content"] . " \"$value\"");
					}
				}
			}

			// On enregistre par la même occasion l'action en historique.
			$server->addActionLogs($remote["server_id"], $action);

			// On affiche ensuite le message de validation.
			echo($translation->getPhrase("global_action_success"));
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