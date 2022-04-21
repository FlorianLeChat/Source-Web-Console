<?php
	//
	// Contrôleur de gestion des authentifications utilisateurs.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement déjà connecté
	//	à un compte utilisateur.
	if (isset($_SESSION["user_id"]))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		exit();
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on définit les limites de caractères pour chaque
		//	champ du formulaire.
		$form->length = [

			// Nom d'utilisateur.
			"username" => [10, 50],

			// Mot de passe.
			"password" => [10, 60]

		];

		// On itére ensuite à travers toutes les clés attendues de la
		//	la requête AJAX pour vérifier les données transmises.
		foreach (array_keys($form->length) as $key)
		{
			// On rend propre et valide l'entrée utilisateur.
			$input = $form->serializeInput($key, $_POST[$key]);

			if ($input === false)
			{
				// Si la donnée est invalide, on casse la boucle et on créé
				//	le message d'erreur approprié.
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

		// On détermine également si l'utilisateur tente de réaliser une demande
		//	de récupération de son mot de passe.
		if (isset($_POST["backup"]))
		{
			// Mise à jour du mot de passe.
			$user->createNewPassword($_POST["username"], $_POST["password"]);

			// Affichage du message final.
			echo(json_encode([$translation->getPhrase("form_signin_recover"), 3]));
			exit();
		}

		// On réalise après certaines actions si les vérifications réussissent.
		if (empty($message))
		{
			// Tentative de connexion à un compte utilisateur.
			if ($user->authenticate($_POST["username"], $_POST["password"]))
			{
				// Si la connexion réussie, on prépare le message de confirmation
				//	dans un premier temps.
				$message = [$translation->getPhrase("form_signin_success"), 2];

				// Dans un second temps, on vérifie si l'utilisateur a demandé de
				//	maintenir sa connexion à travers le temps via les cookies.
				if (isset($_POST["remember_me"]))
				{
					$user->generateToken();
				}
			}
			else
			{
				// Dans le cas contraire, cela signifie que les informations de
				//	de connexion sont invalides.
				$message = [$translation->getPhrase("form_signin_invalid"), 1];
			}
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