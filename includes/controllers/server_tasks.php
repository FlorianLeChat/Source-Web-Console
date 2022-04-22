<?php
	//
	// Contrôleur de gestion des actions et commandes du serveur.
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
		// On récupère une partie des informations reçues dans la requête.
		$target = $_POST["target_server"] ?? 0;
		$date = $_POST["trigger_date"] ?? "";
		$action = $_POST["trigger_action"] ?? "";

		// On tente de récupérer le serveur sélectionné à travers tous
		//	les serveurs enregistrés et appartenant à l'utilisateur.
		$remote = array_filter($server->getServersData($user_id), function(array $remote) use ($target, $user_id)
		{
			return $remote["client_id"] === $user_id && $target == $remote["server_id"];
		});

		// On vérifie alors si le serveur récupéré est valide.
		if (empty($remote))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}
		else
		{
			// Récupération du premier résultat.
			$remote = array_shift($remote);
		}

		// On peut déterminer si l'utilisateur tente de supprimer une tâche
		//	planifiée qui a été créée précédemment.
		$task_id = $_POST["target_task"] ?? 0;
		$server_id = $remote["server_id"];

		if ($task_id !== 0)
		{
			// Suppression de la tâche dans la base de données.
			$server->removeScheduledTask($server_id, $task_id);

			// Message de validation.
			echo($translation->getPhrase("tasks_removed"));
			exit();
		}

		// À ce niveau là, on peut affirmer que l'utilisateur tente d'ajouter
		//	une tâche planifiée et qu'il faut vérifier le reste des données.
		if (empty($date) || empty($action))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		// On ajoute ensuite la tâche dans la base de données.
		$server->addScheduledTask($server_id, $date, $action);

		// On affiche enfin le message de validation à l'utilisateur.
		echo($translation->getPhrase("tasks_added"));
		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>