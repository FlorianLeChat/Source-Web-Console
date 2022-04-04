<?php
	// Point d'entrée de l'environnement des scripts.
	require_once("includes/controllers/_main.php");

	// Récupération des titres et sous-titres des pages.
	$title = "Administration";
	$subtitles = [
		"dashboard" => "Tableau de bord",
		"statistics" => "Statistiques",
		"configuration" => "Configuration",
		"actions" => "Actions et commandes",
		"console" => "Console interactive",
		"tasks" => "Tâches planifiées",
		"help" => "Assistance utilisateur"
	];

	// Rendu final avec le moteur de modèles TWIG.
	$html = $twig->render("$file.twig",
	[
		// Page entière.
		"page_file" => $file,
		"page_title" => "Source Web Console",
		"page_language" => $language,

		// En-tête du document.
		"head_url" => $_SERVER["SERVER_NAME"],
		"head_keywords" => "word1, word2, word3, ...",
		"head_description" => "Description succinte du site...",

		// En-tête de la page.
		"header_title" => $title,
		"header_subtitle" => $subtitles[$file] ?? $title
	]);

	echo($html);
?>