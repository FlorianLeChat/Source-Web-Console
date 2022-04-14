<?php
	//
	// Contrôleur principal de la gestion des données.
	//

	// On initialise le système des sessions PHP.
	if (session_status() !== PHP_SESSION_ACTIVE)
	{
		session_start();
	}

	// On charge les fonctions utilitaires essentielles.
	require_once(__DIR__ . "/helpers/array_functions.php");
	require_once(__DIR__ . "/helpers/string_functions.php");

	// On recherche et on charge tous les modèles de données.
	$models = findFiles(__DIR__ . "/models");

	foreach ($models as $model)
	{
		require_once(__DIR__ . "/models/$model");
	}

	// On tente de connecter automatiquement l'utilisateur si le trafic
	//	du site est sécurité et s'il possède un jeton d'authentification.
	if (isset($_SERVER["HTTPS"]) && !empty($_COOKIE["generated_token"]))
	{
		$user->compareToken($_COOKIE["generated_token"]);
	}
?>