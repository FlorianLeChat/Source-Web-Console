<?php
	// Moteur TWIG.
	require("includes/init_twig.php");

	// Variables fixes.
	$page = $_GET["target"] ?? "index";
	$title = "Administration";
	$language = "FR";
	$titles = [
		"dashboard" => "Tableau de bord",
		"statistics" => "Statistiques",
		"configuration" => "Configuration",
		"actions" => "Actions et commandes",
		"console" => "Console interactive",
		"tasks" => "Tâches planifiées",
		"help" => "Assistance utilisateur"
	];

	$html = $twig->render("$page.twig",
	[
		// Page entière.
		"page_file" => $page,
		"page_title" => "Source Web Console",
		"page_language" => $language,

		// En-tête du document.
		"head_url" => $_SERVER["SERVER_NAME"],
		"head_keywords" => "word1, word2, word3, ...",
		"head_description" => "Description succinte du site...",

		// En-tête de la page.
		"header_title" => $title,
		"header_subtitle" => $titles[$page] ?? $title
	]);

	echo($html);
?>