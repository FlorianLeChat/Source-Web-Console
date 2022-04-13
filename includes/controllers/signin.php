<?php
	//
	// Contrôleur de gestion des authentifications utilisateurs.
	//

	// On vérifie si l'utilisateur est actuellement dans la période
	//	d'attente avant de pouvoir se connecter de nouveau.
	session_start();

	if (isset($_SESSION["form_signin_cooldown"]))
	{
		// Indication : « Too Many Requests ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/429
		http_response_code(429);
		exit();
	}

	// On vérifie si l'utilisateur est déjà connecté.
	if (isset($_SESSION["identifier"]))
	{
		// Indication : « Unauthorized. ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		exit();
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on ajoute certains modèles pour la gestion des
		//	formulaires de contact et des utilisateurs du site.
		require_once(__DIR__ . "/../models/form.php");
		require_once(__DIR__ . "/../models/user.php");

		$form = new Source\Models\Form();
		$user = new Source\Models\User();

		// On définit par la suite les limites de caractères pour chaque
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

		// On réalise après certaines actions si les vérifications réussissent.
		if (empty($message))
		{
			// Tentative de connexion à un compte utilisateur.
			if ($user->authenticate($_POST["username"], $_POST["password"]))
			{
				// Si la connexion réussie, on prépare le message de confirmation
				//	dans un premier temps.
				$message = [$form->translation->getPhrase("form_signin_success"), 2];

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
				$message = [$form->translation->getPhrase("form_signin_invalid"), 1];
			}

			// Mise en mémoire d'une tentative de connexion.
			$_SESSION["form_signin_cooldown"] = true;
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