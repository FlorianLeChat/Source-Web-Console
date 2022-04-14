<?php
	//
	// Routeur de création et de la gestion de l'environnement
	//	d'exécution des scripts PHP et des modèles TWIG.
	//

	// Affichage de toutes les erreurs liées aux scripts PHP.
	ini_set("display_errors", 1);
	ini_set("display_startup_errors", 1);

	error_reporting(E_ALL);

	// Initialisation du contrôleur principal.
	require_once("includes/controller.php");

	// Initialisation du moteur de modèles TWIG.
	require_once("vendor/autoload.php");

	$engine = new Twig\Loader\FilesystemLoader("includes/views");
	$twig = new Twig\Environment($engine, ["debug" => true, "autoescape" => false]);

	// Récupération de la langue demandée par l'utilisateur.
	$language = htmlentities($_POST["language"] ?? "", ENT_QUOTES);

	if (empty($language))
	{
		// La langue est absente des paramètres, on tente de la
		// 	récupérer via l'en-tête HTTP ou via les sessions.
		$language = substr(strtoupper($_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? $translation->getLanguage()), 0, 2);
	}

	// On vérifie alors si la langue est disponible.
	if ($translation->checkLanguage($language))
	{
		// La langue est disponible.
		$translation->setLanguage($language);
	}
	else
	{
		// Dans le cas contraire, on récupère la dernière langue définie.
		$language = $translation->getLanguage();
	}

	// Tentative de connexion automatique avec un jeton d'authentification.
	if (!empty($_COOKIE["generated_token"]))
	{
		$user->compareToken($_COOKIE["generated_token"]);
	}

	// Récupération de la page demandée.
	$file = htmlentities($_GET["target"] ?? "", ENT_QUOTES);

	if (empty($file) || !file_exists("includes/views/$file.twig"))
	{
		// Si la variable est vide ou invalide, on cible la page par défaut.
		$file = "index";
	}

	// Rendu final avec le moteur de modèles TWIG.
	$html = $twig->render("$file.twig",
	[
		// Variables globales.
		"global_url" => $_SERVER["SERVER_NAME"],
		"global_file" => $file,
		"global_phrases" => $translation->getPhrases("global"),
		"global_language" => $language,

		// Variables utilisateurs.
		"user_connected" => isset($_SESSION["identifier"]),
		"user_identifier" => $_SESSION["username"] ?? "",

		// En-tête du document.
		"head_phrases" => $translation->getPhrases("head"),

		// En-tête de la page.
		"header_phrases" => $translation->getPhrases("header"),

		// Contenu de la page.
		"form_phrases" => $translation->getPhrases("form"),
		$file . "_phrases" => $translation->getPhrases($file),

		// Sélecteur de langues.
		"language_codes" => $translation->getLanguages(),
		"language_phrases" => $translation->getPhrases("language"),

		// Pied de page.
		"footer_phrases" => $translation->getPhrases("footer")
	]);

	echo($html);
?>