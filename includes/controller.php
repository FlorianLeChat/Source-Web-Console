<?php
	//
	// Contrôleur principal de la gestion des données.
	//

	// Suppression de l'affichage des erreurs liées aux scripts PHP
	//	dans un environnement de production.
	if ($_SERVER["SERVER_NAME"] !== "localhost")
	{
		// Environnement de production.
		ini_set("display_errors", false);
		ini_set("display_startup_errors", false);

		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
	}

	// Initialisation du système des sessions PHP.
	if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent())
	{
		session_start();
	}

	// Chargement des fichiers prioritaires.
	require_once(__DIR__ . "/helpers/string_functions.php");	// Fonctions utilitaires des chaînes de caractères.
	require_once(__DIR__ . "/helpers/array_functions.php");		// Fonctions utilitaires des tableaux.
	require_once(__DIR__ . "/../vendor/autoload.php");			// Chargeur automatique des bibliothèques.
	require_once(__DIR__ . "/model.php");						// Modèle principal pour tous les scripts.

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

	// Vérification systématique de l'authenticité de l'utilisateur au travers
	//	des services de Google reCAPTCHA pendant la réalisation d'une requête AJAX.
	$recaptcha = $_POST["recaptcha"] ?? "";

	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest")
	{
		// Exécution de la requête de vérification auprès des services Google.
		$secret = $user->getConfig("ReCAPTCHA", "secret_key");
		$request = curl_init();

		curl_setopt($request, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$recaptcha");
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

		$result = json_decode(curl_exec($request), true);

		curl_close($request);

		// Récupération de la réponse et application des mesures adéquates
		//	afin d'empêcher ou non l'exécution du script du formulaire.
		if (is_array($result) && ($result["success"] === false || $result["score"] < 0.7))
		{
			// Indication : « Unauthorized ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
			http_response_code(401);
			exit();
		}
	}
?>