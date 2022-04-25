<?php
	//
	// Contrôleur de gestion des actions et commandes du serveur.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie qu'il ne s'agit pas d'une exécution via les tâches
	//	CRON interne au système d'exploitation.
	//	Note : dans ce mode, le code peut parfois être moins « soigné »...
	//	Source : https://stackoverflow.com/a/22358929
	//	Exemple : /usr/bin/php8.0 /var/www/console.florian-dev.fr/includes/controllers/server_tasks.php
	if (php_sapi_name() === "cli")
	{
		// Récupération de toutes les tâches planifiées en fonction
		//	de leur état et de la date de déclenchement.
		//	Note : on définit le fuseau horaire arbitrairement sur celui
		//		de Paris même si les utilisateurs sont hors de France.
		date_default_timezone_set("Europe/Paris");

		$query = $server->connector->prepare("SELECT `task_id`, `server_id`, `action` FROM `tasks` WHERE `date` = ? AND `state` = 'WAITING';");
			$query->bindValue(1, date("Y-m-d H:i:00"));
		$query->execute();

		$tasks = $query->fetchAll() ?? [];

		// Calcul du nombre de tâches récupérées.
		$count = count($tasks);

		if ($count === 0)
		{
			echo("Aucune tâche ne peut être récupérer pour le moment." . PHP_EOL);
			exit();
		}
		else
		{
			echo("Récupération de " . count($tasks) . " tâches planifiées." . PHP_EOL);
		}

		// Itération à travers toutes les tâches.
		foreach ($tasks as $task)
		{
			echo("Début de l'exécution de la tâche " . $task["task_id"] . "." . PHP_EOL);

			// Récupération des données du serveur.
			$query = $server->connector->prepare("SELECT * FROM `servers` WHERE `server_id` = ?");
				$query->bindValue(1, $task["server_id"]);
			$query->execute();

			$remote = $query->fetch();

			if (is_array($tasks) && count($tasks) > 0)
			{
				try
				{
					// Tentative de connexion distante au serveur.
					//	Note : c'est un copier/coller du fichier « server_actions.php ».
					$server->connectServer($remote["admin_address"] ?? $remote["client_address"], $remote["admin_port"] ?? $remote["client_port"], $remote["admin_password"]);

					switch ($task["action"])
					{
						case "shutdown":
						{
							// Arrêt du serveur.
							$server->query->Rcon("sv_shutdown");
							break;
						}

						case "restart":
						{
							// Redémarrage du serveur.
							$server->query->Rcon("_restart");
							break;
						}

						case "update":
						{
							// Mise à jour du serveur.
							$server->query->Rcon("svc_update");
							break;
						}

						case "service":
						{
							// Maintenance du serveur.
							$server->query->Rcon("sv_password \"password\"");
							break;
						}
					}

					// Signalisation de fin d'exécution.
					$query = $server->connector->prepare("UPDATE `tasks` SET `state` = 'DONE' WHERE `task_id` = ?;");
						$query->bindValue(1, $task["task_id"]);
					$query->execute();

					echo("Fin d'exécution. Tâche marquée comme « terminée »." . PHP_EOL);
				}
				catch (Exception $error)
				{
					// Signalisation de l'erreur fatale.
					$query = $server->connector->prepare("UPDATE `tasks` SET `state` = 'ERROR' WHERE `task_id` = ?;");
						$query->bindValue(1, $task["task_id"]);
					$query->execute();

					echo("Erreur fatale. Tâche marquée comme « échouée »." . PHP_EOL);
				}
				finally
				{
					// Déconnexion du serveur distant.
					$server->query->Disconnect();
				}
			}
		}
	}

	// Dans un cas classique, on vérifie d'abord si l'utilisateur
	//	est actuellement connecté à un compte utilisateur.
	$user_id = $_SESSION["user_id"];

	if (empty($user_id))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		exit();
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on tente de récupérer le serveur sélectionné ainsi
		// 	que les données transmises dans la requête.
		$target = $_POST["target_server"] ?? 0;
		$date = $_POST["trigger_date"] ?? "";
		$action = $_POST["trigger_action"] ?? "";

		// On tente de récupérer le serveur sélectionné à travers tous
		//	les serveurs enregistrés et appartenant à l'utilisateur.
		$remote = array_filter($server->getServersData($user_id), function(array $remote) use ($target, $user_id)
		{
			return $remote["client_id"] === $user_id && $target == $remote["server_id"];
		});

		// On vérifie alors si le serveur récupéré est valide.
		if (empty($remote))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}
		else
		{
			// Récupération du premier résultat.
			$remote = array_shift($remote);
		}

		// On peut déterminer si l'utilisateur tente de supprimer une tâche
		//	planifiée qui a été créée précédemment.
		$task_id = $_POST["target_task"] ?? 0;
		$server_id = $remote["server_id"];

		if ($task_id !== 0)
		{
			// Suppression de la tâche dans la base de données.
			$server->removeScheduledTask($server_id, $task_id);

			// Message de validation.
			echo($translation->getPhrase("tasks_removed"));
			exit();
		}

		// À ce niveau là, on peut affirmer que l'utilisateur tente d'ajouter
		//	une tâche planifiée et qu'il faut vérifier le reste des données.
		if (empty($date) || empty($action))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		// On ajoute ensuite la tâche dans la base de données.
		$server->addScheduledTask($server_id, $date, $action);

		// On affiche enfin le message de validation à l'utilisateur.
		echo($translation->getPhrase("tasks_added"));
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>