<?php
	//
	// Contrôleur de gestion des inscriptions utilisateurs.
	//

	// On vérifie d'abord si la page est demandée avec une requête AJAX.
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

			// Taille des champs du compte utilisateur.
			"username" => [10, 50],
			"password" => [10, 60],

			// Taille des champs client (premier serveur).
			"server_address" => [10, 15],
			"server_port" => [5, 5]

		];

		// On itére ensuite à travers toutes les clés attendues de la
		//	la requête POST pour vérifier les données transmises.
		foreach (array_keys($form->length) as $key)
		{
			// On rend propre et valide l'entrée utilisateur.
			$value = $form->serializeInput($_POST, $key);

			if (!$value)
			{
				// Si la donnée est invalide, on casse la boucle et
				//	on créé le message d'erreur approprié.
				$message = [$form->formatMessage($key), 1];
				break;
			}
		}

		// On réalise après certaines actions si les vérifications ont réussies.
		if (empty($message))
		{
			// Tentative d'ajout du nouveau compte utilisateur.
			if ($user->register($_POST["username"], $_POST["password"]))
			{
				// Si l'inscription réussie, on l'indique à l'utilisateue et
				//	on enregistre le premier serveur dans la base de données.
				$message = [$form->translation->getPhrase("form_signup_success"), 2];

				// $server->add();
				// check secureonly + auto_connect
			}
			else
			{
				// Dans le cas contraire, cela signifie que le nom d'utilisateur
				//	indiqué a déjà été utilisé par quelqu'un d'autre.
				$message = [$form->translation->getPhrase("form_signup_duplication"), 1];
			}
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