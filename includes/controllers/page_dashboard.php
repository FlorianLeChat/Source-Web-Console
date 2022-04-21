<?php
	//
	// Contrôleur de gestion de la page du tableau de bord.
	//

	// On récupère toutes les instances liées à l'utilisateur actuelle.
	$client_id = $_SESSION["identifier"];
	$instances = $server->getInstances($client_id);

	// On vérifie ensuite si la page a été demandée sous une requête de
	//	type POST. Cela signifie que l'utilisateur tente d'effectuer une
	//	action sur une des instances.
	$address = $_POST["address"] ?? "";

	if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($address))
	{
		// On filtre d'abord toutes les instances pour trouver celle qui a
		//	été sélectionnée par l'utilisateur.
		$target_instance = array_filter($instances, function(array $instance)
		{
			// L'adresse IP peut être celle côté client mais également celle
			//	renseignée pour accéder au module d'administration.
			$client_address = $instance["client_address"] . ":" . $instance["client_port"];	// Côté client.
			$admin_address = $instance["admin_address"] . ":" . $instance["admin_port"];	// Côté administrateur.

			global $address;
			return $client_address === $address || $admin_address === $address;
		});

		// On récupère la première valeur trouvée dans la liste.
		$target_instance = array_shift($target_instance);

		// On tente ensuite de déterminer l'action
		$action = $_POST["action"] ?? "connect";
		$server_id = $target_instance["server_id"];

		switch ($action)
		{
			case "edit":
			{
				// Édition d'une instance.
				//	Note : dans tous les cas, les valeurs sont actualisées avec
				//		celles indiquées par l'utilisateur ou ceux actuellement
				//		présentes dans la base de données.
				$admin_password = tryGetValue($server->password_encrypt($_POST["admin_password"]), $target_instance["admin_password"]);
				$client_address = tryGetValue($_POST["client_address"], $target_instance["client_address"]);
				$admin_address = tryGetValue($_POST["admin_address"], $target_instance["admin_address"]);
				$client_port = tryGetValue($_POST["client_port"], $target_instance["client_port"]);
				$admin_port = tryGetValue($_POST["admin_port"], $target_instance["admin_port"]);

				$server->updatePublicInstance($client_id, $server_id, $client_address, $client_port);
				$server->storeAdminCredentials($client_id, $server_id, $admin_address, $admin_port, $admin_password);

				break;
			}

			case "delete":
			{
				// Suppression d'une instance.
				$server->deletePublicInstance($client_id, $server_id);
				break;
			}

			default:
			{
				// Connexion à une instance, rien à faire.
				break;
			}
		}

		// On recharge la page à la fin des modifications afin de rafraîchir
		//	les informations visibles sur la page d'accueil.
		if ($action !== "connect")
		{
			header("Location: " . $_SERVER["REQUEST_URI"]);
			exit();
		}
	}

	// On vérifie après si une instance a bien été sélectionnée à l'étape
	//	précédente (méthode de requête classique).
	if (empty($target_instance))
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
		"dashboard_current_server" => $target_instance

	];
?>