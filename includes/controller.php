<?php
	//
	// Contrôleur principal de la gestion des données.
	//

	// Affichage de toutes les erreurs liées aux scripts PHP.
	ini_set("display_errors", 1);
	ini_set("display_startup_errors", 1);

	error_reporting(E_ALL);

	// Initialisation du système des sessions PHP.
	if (session_status() !== PHP_SESSION_ACTIVE)
	{
		session_start();
	}

	// Chargement des fichiers prioritaires.
	require_once(__DIR__ . "/helpers/string_functions.php");
	require_once(__DIR__ . "/helpers/array_functions.php");
	require_once(__DIR__ . "/model.php");

	// Recherche et chargement de tous les modèles de données.
	$models = findFiles(__DIR__ . "/models");

	foreach ($models as $model)
	{
		require_once(__DIR__ . "/models/$model");
	}

	// Création de toutes les classes nécessaires.
	$form = new Source\Models\Form();
	$user = new Source\Models\User();
	$mail = new Source\Models\Mail();
	$server = new Source\Models\Server();
	$translation = new Source\Models\Language();
?>