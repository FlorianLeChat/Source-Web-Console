<?php
	//
	// Contrôleur de gestion de la page du tableau de bord.
	//

	// On récupère d'abord l'identifiant unique de l'utilisateur et d'un
	//	serveur possiblement sélectionné depuis le tableau de bord.
	$user_id = $_SESSION["user_id"] ?? 0;
	$server_id = $_POST["server_id"] ?? $_SESSION["server_id"] ?? 0;

	// On récupère tous les serveurs liés au compte de l'utilisateur.
	$remotes = $server->getServersData($user_id);

	// On tente de récupérer par la même occasion le serveur sélectionné
	//	par l'utilisateur.
	if (!empty($server_id))
	{
		// Filtrage de tous les serveurs par identifiant unique.
		$target_remote = array_filter($remotes, function(array $remote) use ($server_id)
		{
			return $remote["server_id"] == $server_id;
		});
	}
	else
	{
		// Filtrage de tous les serveurs par connexion automatique.
		$target_remote = array_filter($remotes, function(array $remote)
		{
			return $remote["auto_connect"] === 1;
		});
	}

	// On vérifie si on a pu récupérer un serveur lors de l'étape précédente
	//	pour la suite des opérations.
	if (!empty($target_remote))
	{
		// On récupère la première valeur des résultats de filtrage avant
		//	mettre à jour l'identifiant unique du serveur.
		$target_remote = array_shift($target_remote);
		$server_id = $target_remote["server_id"];

		// On vérifie si la page a été demandée sous une requête de type POST.
		if ($_SERVER["REQUEST_METHOD"] === "POST")
		{
			// On détermine alors le type d'action a réaliser sur le serveur.
			$action = $_POST["server_action"] ?? "connect";

			switch ($action)
			{
				case "edit":
				{
					// Édition d'un serveur.
					//	Note : dans tous les cas, les valeurs sont actualisées avec
					//		celles indiquées par l'utilisateur ou ceux actuellement
					//		présentes dans la base de données.
					$client_address = tryGetValue($_POST["client_address"], $target_remote["client_address"]);
					$client_port = tryGetValue($_POST["client_port"], $target_remote["client_port"]);

					$admin_address = tryGetValue($_POST["admin_address"], $target_remote["admin_address"]);
					$admin_port = tryGetValue($_POST["admin_port"], $target_remote["admin_port"]);
					$admin_password = tryGetValue($server->encryptPassword($_POST["admin_password"]), $target_remote["admin_password"]);

					$server->updateServer($user_id, $server_id, $client_address, $client_port);
					$server->storeAdminCredentials($user_id, $server_id, $admin_address, $admin_port, $admin_password);

					break;
				}

				case "delete":
				{
					// Suppression d'un serveur.
					$server->deleteServer($user_id, $server_id);
					break;
				}

				default:
				{
					// Connexion à un serveur, rien à faire.
					break;
				}
			}

			// On recharge la page après avoir procédé à la modification
			//	des données afin d'afficher des informations actualisées.
			//	Note : cela ne s'applique pas aux demandes de connexion.
			if ($action !== "connect")
			{
				header("Location: " . $_SERVER["REQUEST_URI"]);
				exit();
			}
		}

		// On sauvegarde également l'identifiant unique en session pour
		//	pouvoir garder cette information sur les autres pages.
		$_SESSION["server_id"] = $server_id;
	}

	// On implémente ensuite une fonction TWIG afin de déterminer le
	//	nom complet du jeu actuellement utilisé sur le serveur.
	$function = new \Twig\TwigFunction("getNameByGameID", function(int $identifier, string $fallback) use ($server)
	{
		return $server->getNameByGameID($identifier, $fallback);
	});

	$twig->addFunction($function);

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Récupération de l'historique des actions et commandes.
		"dashboard_logs" => $server_id ? $server->getActionLogs($server_id) : [],

		// Liste des serveurs depuis la base de données.
		"dashboard_servers" => $remotes

	];
?>