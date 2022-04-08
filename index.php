<?php
	// Point d'entrée de l'environnement des scripts.
	require_once("includes/controllers/_main.php");
	require_once("includes/views/$file.php");

	// Rendu final avec le moteur de modèles TWIG.
	$html = $twig->render("$file.twig",
	[
		// Variables globales.
		"global_url" => $_SERVER["SERVER_NAME"],
		"global_file" => $file,
		"global_phrases" => $translation->getPhrases("global"),
		"global_language" => $language,

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