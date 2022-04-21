<?php
	//
	// Contrôleur de gestion des inscriptions utilisateurs.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement dans la période
	//	d'attente avant d'envoyer une nouvelle inscription.
	if (isset($_SESSION["form_signup_cooldown"]))
	{
		// Indication : « Too Many Requests ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/429
		http_response_code(429);
		exit();
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on définit les limites de caractères pour chaque
		//	champ du formulaire.
		$form->length = [

			// Taille des champs du compte utilisateur.
			"username" => [10, 50],
			"password" => [10, 60],

			// Taille des champs client (premier serveur).
			"server_address" => [10, 15],
			"server_port" => [5, 5],

			// Taille des champs administrateur (facultatif).
			//	Note : le mot de passe n'a pas de restriction.
			"admin_address" => [10, 15],
			"admin_port" => [5, 5]

		];

		// On vérifie si l'utilisateur ne tente pas de créer un compte
		//	à usage unique et donc ne nécessite pas la création d'un
		//	compte utilisateur classique.
		if (empty($_POST["username"]) && empty($_POST["password"]))
		{
			// On enregistre les données à notre possession.
			$_SESSION["temporary_user"] = true;
			$_SESSION["server_data"] = $_POST;

			// On retourne alors la phrase signifiant que l'accès unique
			//	a été créé avec succès.
			echo(json_encode([$translation->getPhrase("form_signup_onetime"), 2]));
			exit();
		}

		// On itére ensuite à travers toutes les clés attendues de la
		//	la requête AJAX pour vérifier les données transmises.
		foreach (array_keys($form->length) as $key)
		{
			// On rend propre et valide l'entrée utilisateur.
			$input = $form->serializeInput($key, $_POST[$key]);

			if ($input === false)
			{
				// Si la donnée est invalide, on regarde qu'il ne s'agit pas
				//	d'une information administration, dans ce cas on vérifie
				//	si l'entrée est vide ou non.
				if (str_starts_with($key, "admin") && empty($_POST[$key]))
				{
					continue;
				}

				// On affiche tout simplement le message d'erreur approprié.
				$message = [$form->formatMessage($key), 1];
				break;
			}
			else
			{
				// Dans le cas contraire, on met à jour les données reçues par
				//	la requête AJAX.
				$_POST[$key] = $input;
			}
		}

		// On réalise après certaines actions si les vérifications réussissent.
		if (empty($message))
		{
			// Tentative d'ajout du nouveau compte utilisateur.
			if ($user->register($_POST["username"], $_POST["password"]))
			{
				// Si l'inscription réussie, on prépare le message de confirmation
				//	dans un premier temps.
				$message = [$translation->getPhrase("form_signup_success"), 2];

				// Dans un second temps, on ajoute le serveur enregistré dans la
				//	base de données du site.
				$client_id = $_SESSION["user_id"];
				$server->storeServer($client_id, $_POST["server_address"], $_POST["server_port"], $_POST["secure_only"], $_POST["auto_connect"]);

				if (!empty($_POST["admin_address"]))
				{
					// Les informations sont facultatives, donc on vérifie leur présence
					//	avant de les ajouter eux aussi dans la base de données.
					$server->storeAdminCredentials($client_id, $server->connector->lastInsertId(), $_POST["admin_address"], $_POST["admin_port"], $server->encryptPassword($_POST["admin_password"]));
				}
			}
			else
			{
				// Dans le cas contraire, cela signifie que le nom d'utilisateur
				//	indiqué a déjà été utilisé par quelqu'un d'autre.
				$message = [$translation->getPhrase("form_signup_duplication"), 1];
			}

			// Mise en mémoire d'une tentative d'inscription.
			$_SESSION["form_signup_cooldown"] = true;
		}

		// On affiche enfin le message final.
		echo(json_encode($message));
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>