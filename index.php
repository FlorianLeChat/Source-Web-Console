<?php
	// Moteur TWIG.
	require("includes/init_twig.php");

	// Variables fixes.
	$language = "FR";
	$title = "Source Web Console";

	// En-tête de la page.
	$head_html = $twig->render("1_head.twig",
	[
		"head_language" => $language,
		"head_description" => "Description succinte du site...",
		"head_keywords" => "word1, word2, word3, ...",
		"head_title" => $title
	]);

	// Formulaires.
	$signup_html = $twig->render("2_signup.twig");
	$signin_html = $twig->render("3_signin.twig");
	$contact_html = $twig->render("4_contact.twig");

	// Pied-de-page.
	$footer_html = $twig->render("5_footer.twig");

	// Page entière.
	$main_html = $twig->render("index.twig",
	[
		"head_language" => "FR",
		"head_title" => "Source Web Console",
		"head_html" => $head_html,

		"body_signup" => $signup_html,
		"body_signin" => $signin_html,
		"body_contact" => $contact_html,

		"footer_options" => $footer_html
	]);

	echo($main_html);
?>