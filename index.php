<?php
	// Moteur TWIG.
	require("includes/init_twig.php");

	// Variables fixes.
	$page = "index";
	$title = "Source Web Console";
	$language = "FR";

	// En-tête du document.
	$head_html = $twig->render("1_head.twig",
	[
		"file" => $page,
		"title" => $title,
		"language" => $language,
		"keywords" => "word1, word2, word3, ...",
		"description" => "Description succinte du site..."
	]);

	// En-tête de la page.
	$header_html = $twig->render("2_header.twig",
	[
		"title" => "Source Web Console"
	]);

	// Barre de navigation.
	$navigation_html = $twig->render("3_navigation.twig");

	// Formulaires.
	$signup_html = $twig->render("4_signup.twig");
	$signin_html = $twig->render("5_signin.twig");
	$contact_html = $twig->render("6_contact.twig");

	// Pied-de-page.
	$footer_html = $twig->render("7_footer.twig");

	// Page entière.
	$main_html = $twig->render("$page.twig",
	[
		"title" => "Source Web Console",
		"language" => "FR",

		"head_html" => $head_html,
		"header_html" => $header_html,
		"navigation_html" => $navigation_html,
		"signup_html" => $signup_html,
		"signin_html" => $signin_html,
		"contact_html" => $contact_html,
		"footer_html" => $footer_html
	]);

	echo($main_html);
?>