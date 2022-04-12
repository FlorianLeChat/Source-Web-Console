<?php
	//
	// Contrôleur de gestion des formulaires de contact.
	//

	// On vérifie d'abord si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhtprequest")
	{
		// Si c'est le cas, on ajoute le modèle de gestion des formulaires
		//	du site.
		require_once(__DIR__ . "/../models/form.php");

		$form = new Source\Models\Form();
		$form->length = [

			// Taille de l'adresse électronique.
			"email" => [10, 40],

			// Taille du sujet de contact.
			"subject" => [1, 15],

			// Taille du contenu du message.
			"content" => [50, 4000]

		];

		// On itére ensuite à travers toutes les clés attendues de la
		//	la requête POST pour vérifier les données transmises.
		foreach (array_keys($form->length) as $key)
		{
			// On rend propre et valide l'entrée utilisateur.
			$value = $form->serializeInput($_POST, $key);

			if ($value)
			{
				// Si la donnée est valide, alors on force la mise en
				//	place d'une majuscule à l'entrée.
				$_POST[$key] = $form->capitalize($value);
			}
			else
			{
				// Dans le cas contraire, on casse la boucle et on créé
				//	le message d'erreur approprié.
				$message = [$form->formatMessage($key), 1];
				break;
			}
		}

		// On réalise après certaines actions si les vérifications ont réussies.
		if (empty($message))
		{
			// Ajout du message de validation.
			$message = [$form->translation->getPhrase("form_contact_success"), 2];

			// Insertion du message dans la base de données.
			$form->insertMessage($_POST["email"], $_POST["subject"], $_POST["content"]);
		}

		// On affiche enfin le message final.
		echo(json_encode($message));
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	http_response_code(405);
	exit();
?>