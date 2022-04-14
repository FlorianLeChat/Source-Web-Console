<?php
	//
	// Contrôleur de gestion des formulaires de contact.
	//

	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement dans la période
	//	d'attente avant d'envoyer un nouveau message.
	if (isset($_SESSION["form_contact_cooldown"]))
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

			// Taille de l'adresse électronique.
			"email" => [10, 40],

			// Taille du sujet de contact.
			"subject" => [1, 15],

			// Taille du contenu du message.
			"content" => [50, 4000]

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
				// Dans le cas contraire, alors on force la mise en place
				//	d'une majuscule à la première lettre avant de mettre à
				//	jour les données reçues par la requête AJAX.
				$_POST[$key] = capitalize($input);
			}
		}

		// On réalise après certaines actions si les vérifications réussissent.
		if (empty($message))
		{
			// Insertion du message dans la base de données.
			$form->insertMessage($_POST["email"], $_POST["subject"], $_POST["content"]);

			// Ajout du message de validation.
			$message = [$form->translation->getPhrase("form_contact_success"), 2];
		}

		// On met en mémoire que l'utilisateur a effectué une inscription.
		$_SESSION["form_contact_cooldown"] = true;

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