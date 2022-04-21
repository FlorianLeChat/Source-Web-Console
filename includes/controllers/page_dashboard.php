<?php
	//
	// Contrôleur de gestion de la page du tableau de bord.
	//

	// On récupère d'abord l'identifiant unique de l'utilisateur et d'une
	//	instance possiblement sélectionnée.
	$client_id = $_SESSION["user_id"];
	$server_id = $_POST["server_id"] ?? "";

	// On récupère ensuite les instances liées au compte de l'utilisateur.
	$instances = $server->getInstances($client_id);

	// On vérifie ensuite si la page a été demandée sous une requête de
	//	type POST. Cela signifie que l'utilisateur tente d'effectuer une
	//	action sur une instance bien précise.
	if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($server_id))
	{
		$target_instance = array_filter($instances, function(array $instance)
		{
			global $server_id;
			return $instance["server_id"] == $server_id;
		});

		// Récupération de la première valeur.
		$target_instance = array_shift($target_instance);
	}

	// On vérifie après le résultat de la récupération effectuée lors de
	//	l'étape précédente.
	if (!empty($target_instance) && is_array($target_instance))
	{
		// Si on semble avoir les données d'une instance, on tente de déterminer
		//	le type d'action à réaliser.
		$action = $_POST["server_action"] ?? "connect";

		switch ($action)
		{
			case "edit":
			{
				// Édition d'une instance.
				//	Note : dans tous les cas, les valeurs sont actualisées avec
				//		celles indiquées par l'utilisateur ou ceux actuellement
				//		présentes dans la base de données.
				$client_address = tryGetValue($_POST["client_address"], $target_instance["client_address"]);
				$client_port = tryGetValue($_POST["client_port"], $target_instance["client_port"]);

				$admin_address = tryGetValue($_POST["admin_address"], $target_instance["admin_address"]);
				$admin_port = tryGetValue($_POST["admin_port"], $target_instance["admin_port"]);
				$admin_password = tryGetValue($server->password_encrypt($_POST["admin_password"]), $target_instance["admin_password"]);

				$server->updatePublicInstance($client_id, $target_instance["server_id"], $client_address, $client_port);
				$server->storeAdminCredentials($client_id, $target_instance["server_id"], $admin_address, $admin_port, $admin_password);

				break;
			}

			case "delete":
			{
				// Suppression d'une instance.
				$server->deletePublicInstance($client_id, $target_instance["server_id"]);
				break;
			}

			default:
			{
				// Connexion à une instance, rien à faire.
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
		//	rechercher si l'une des instances doit se connecter automatiquement
		//	lors de l'arrivée sur le tableau de bord.
		$target_instance = array_filter($instances, function(array $instance)
		{
			return $instance["auto_connect"] === 1;
		});

		// On récupère la première valeur trouvée dans la liste.
		$target_instance = array_shift($target_instance);
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

		// Liste des instances depuis la base de données.
		"dashboard_instances" => $instances,

		// Récupération de l'instance qui doit se connecter automatiquement.
		"dashboard_server_identifier" => $target_instance["server_id"]

	];
?>