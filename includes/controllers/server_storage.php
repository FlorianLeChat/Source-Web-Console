<?php
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

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on tente de récupérer le serveur sélectionné ainsi
		// 	que les données transmises dans la requête.
		$remote = $server->getServerData($user_id, $_SESSION["server_id"] ?? 0);
		$storage = $server->getExternalStorage($remote["server_id"]);

		$action = $_POST["ftp_action"] ?? "";
		$address = $_POST["ftp_address"] ?? $storage["host"] ?? "";
		$port = $_POST["ftp_port"] ?? $storage["port"] ?? "";
		$protocol = $_POST["ftp_protocol"] ?? $storage["protocol"] ?? "";

		if (empty($remote) || empty($action) || empty($address) || empty($port) || empty($protocol))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		// On récupère ensuite les dernières informations tranmises dans la
		//	requête avant de déterminer l'action a réaliser.
		$username = $_POST["ftp_user"] ?? $storage["username"] ?? "";
		$password = $_POST["ftp_password"] ?? $storage["password"];

		if ($password === $storage["password"])
		{
			// Déchiffrement du mot de passe à la volée.
			$password = $server->decryptPassword($password);
		}

		if ($action === "insert")
		{
			// Insertion des données dans la base de données.
			$server->addExternalStorage($remote["server_id"], $address, $port, $protocol, $username, $password);
		}
		elseif ($action === "update")
		{
			// Mise à jour des données dans la base de données.
			$server->updateExternalStorage($remote["server_id"], $address, $port, $protocol, $username, $password);
		}
		elseif ($action === "connexion")
		{
			// Récupération du type de protocole.
			$stream = $server->openFTPConnection($address, $port, $username, $password, $protocol);

			if ($stream !== null)
			{
				// Récupération du fichier distant.
				$content = $server->getFTPFileContents($stream, "serverfiles/garrysmod/cfg/gmodserver.cfg", $protocol);

				// Récupération du type d'action/valeur qui doit être réalisé.
				$type = $_POST["ftp_type"] ?? "";
				$value = $_POST["ftp_value"] ?? "";

				switch ($type)
				{
					case "hostname":
					{
						// Édition du nom du serveur.
						$content = preg_replace("/hostname \"(.*)\"/i", "hostname \"" . $value . "\"", $content);
						break;
					}

					case "loading":
					{
						// Édition de l'écran de chargement.
						$content = preg_replace("/sv_loadingurl \"(.*)\"/i", "sv_loadingurl \"" . $value . "\"", $content);
						break;
					}

					case "password":
					{
						// Édition du mot de passe d'accès.
						$content = preg_replace("/rcon_password \"(.*)\"/i", "rcon_password \"" . $value . "\"", $content);
						break;
					}
				}

				// Téléversement des modifications.
				$server->putFTPFileContents($stream, "serverfiles/garrysmod/cfg/gmodserver.cfg", $content, $protocol);
			}
		}

		// On affiche enfin le message de validation.
		echo($translation->getPhrase("global_action_success"));
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>