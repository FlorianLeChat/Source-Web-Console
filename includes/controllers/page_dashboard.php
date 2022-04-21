<?php
	//
	// Contrôleur de gestion de la page du tableau de bord.
	//

	// On récupère d'abord l'identifiant unique de l'utilisateur et d'un
	//	serveur possiblement sélectionné.
	$client_id = $_SESSION["user_id"];
	$server_id = $_POST["server_id"] ?? $_SESSION["server_id"] ?? "";

	// On récupère ensuite les serveurs liés au compte de l'utilisateur.
	$remotes = $server->getServersData($client_id);

	// On vérifie ensuite si la page a été demandée sous une requête de
	//	type POST. Cela signifie que l'utilisateur tente d'effectuer une
	//	action sur un serveur bien précis.
	if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($server_id))
	{
		$target_remote = array_filter($remotes, function(array $remote)
		{
			global $server_id;
			return $remote["server_id"] == $server_id;
		});

		// Récupération de la première valeur.
		$target_remote = array_shift($target_remote);

		// Sauvegarde de la valeur en session.
		$_SESSION["server_id"] = $server_id;
	}

	// On vérifie après le résultat de la récupération effectuée lors de
	//	l'étape précédente.
	if (!empty($target_remote) && is_array($target_remote))
	{
		// Si on semble avoir les données d'un serveur, on tente de déterminer
		//	le type d'action à réaliser.
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
				$admin_password = tryGetValue($server->password_encrypt($_POST["admin_password"]), $target_remote["admin_password"]);

				$server->updateServer($client_id, $target_remote["server_id"], $client_address, $client_port);
				$server->storeAdminCredentials($client_id, $target_remote["server_id"], $admin_address, $admin_port, $admin_password);

				break;
			}

			case "delete":
			{
				// Suppression d'un serveur.
				$server->deleteServer($client_id, $target_remote["server_id"]);
				break;
			}

			default:
			{
				// Connexion à un serveur, rien à faire.
				break;
			}
		}

		// On recharge enfin la page après avoir la page afin de rafraîchir
		//	les informations visibles sur la page d'accueil.
		if ($action !== "connect")
		{
			header("Location: " . $_SERVER["REQUEST_URI"]);
			exit();
		}
	}
	else
	{
		// Si ce n'est pas le cas, alors on fait la recherche habituelle pour
		//	rechercher si l'un des serveur doit se connecter automatiquement
		//	lors de l'arrivée sur le tableau de bord.
		$target_remote = array_filter($remotes, function(array $remote)
		{
			return $remote["auto_connect"] === 1;
		});

		// On récupère la première valeur trouvée dans la liste.
		$target_remote = array_shift($target_remote);
	}

	// On implémente également une fonction TWIG afin de déterminer le
	//	nom complet du jeu actuellement utilisé sur le serveur.
	$function = new \Twig\TwigFunction("getNameByGameID", function(int $identifier, string $fallback)
	{
		global $server;
		return $server->getNameByGameID($identifier, $fallback);
	});

	$twig->addFunction($function);

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Récupération de l'historique des actions et commandes.
		"dashboard_logs" => $server->getActionLogs($target_remote["server_id"]),

		// Liste des serveurs depuis la base de données.
		"dashboard_servers" => $remotes

	];
?>