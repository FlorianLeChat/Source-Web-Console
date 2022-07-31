<?php
	//
	// Routeur de création et de la gestion de l'environnement
	//	d'exécution des scripts PHP et des modèles TWIG.
	//

	// Initialisation du contrôleur principal.
	require_once("includes/controller.php");

	// Initialisation du moteur de modèles TWIG.
	$engine = new Twig\Loader\FilesystemLoader("includes/views");
	$twig = new Twig\Environment($engine, ["autoescape" => false]);

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

	// Récupération de la page demandée par l'utilisateur.
	$file = htmlentities($_GET["target"] ?? "", ENT_QUOTES);

	if (empty($file) || !file_exists("includes/views/$file.twig"))
	{
		// Si la variable est vide ou invalide, on cible la page par défaut.
		$file = "index";
	}

	// Vérification de l'état de connexion de l'utilisateur.
	$connected = isset($_SESSION["user_id"]);

	if (!$connected)
	{
		// Si l'utilisateur est déconnecté, on tente de le connecter
		//	automatiquement à son compte avec un jeton d'authentification
		//	enregistré sur son navigateur.
		if (!empty($_COOKIE["generated_token"]))
		{
			// Récupération de l'état de connexion après comparaison avec les
			//	données présentes dans la base de données.
			$connected = $user->compareToken($_COOKIE["generated_token"]);
		}

		// Si malgré la tentative de connexion automatique, l'utilisateur
		//	est toujours déconnecté et qu'il veut accès à une page protégé
		//	alors, on force l'affichage de la page d'accueil.
		if (!$connected && ($file !== "index" && $file !== "legal" && $file !== "help"))
		{
			$file = "index";
		}
	}

	// Exécution du script PHP pour le fichier spécifique.
	if (file_exists("includes/controllers/page_$file.php"))
	{
		include_once("includes/controllers/page_$file.php");
	}

	// Assemblage des paramètres du moteur TWIG.
	// 	Note : dans cette partie, les paramètres dynamiques possiblement créés
	//		dans le script PHP de la page actuel sont fusionnés avec ceux qui
	//		ont été prédéfinis par défaut.
	$parameters = array_merge($parameters ?? [], [

		// Variables globales.
		"global_url" => "http" . (!empty($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER["HTTP_HOST"] . "/",
		"global_file" => $file,
		"global_phrases" => $translation->getPhrases("global"),
		"global_captcha" => $user->getConfig("captcha_public_key"),
		"global_language" => $language,
		"global_javascript" => file_exists("scripts/$file.js"),

		// Variables utilisateurs.
		"user_level" => $_SESSION["user_level"] ?? "standard",
		"user_connected" => $connected,
		"user_identifier" => $_SESSION["user_name"] ?? "",

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

	// Rendu final avec le moteur TWIG.
	echo($twig->render("$file.twig", $parameters));
?>