<?php
	//
	// Contrôleur de gestion des informations utilisateurs.
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

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on tente de récupérer l'action demandée
		//	par l'utilisateur.
		$action = $_POST["user_action"] ?? "";

		switch ($action)
		{
			case "insert":
			{
				// Ajout d'un nouveau serveur.
				$server->storeServer($user_id, $_POST["server_address"] ?? "", $_POST["server_port"] ?? "", $_POST["secure_only"], $_POST["auto_connect"]);

				if (!empty($_POST["admin_address"]))
				{
					// Informations facultatives.
					$server->storeAdminCredentials($user_id, $server->connector->lastInsertId(), $_POST["admin_address"], $_POST["admin_port"], $server->encryptPassword($_POST["admin_password"]));
				}

				$message = $translation->getPhrase("user_insert");
				break;
			}

			case "update":
			{
				// Mise à jour des informations.
				$user->update($_POST["user_name"] ?? "", $_POST["user_password"] ?? "");
				$message = $translation->getPhrase("user_updated");
				break;
			}

			case "remove":
			{
				// Suppression du compte utilisateur.
				$user->remove();
				$user->destroy();
				$message = $translation->getPhrase("user_removed");
				break;
			}

			case "reconnect":
			{
				// Reconnexion au compte utilisateur.
				$message = $translation->getPhrase("user_reconnected");
				break;
			}

			case "disconnect":
			{
				// Déconnexion du compte utilisateur.
				$user->destroy();
				$message = $translation->getPhrase("user_disconnected");
				break;
			}
		}

		// On affiche enfin le message de validation.
		echo($message);
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>