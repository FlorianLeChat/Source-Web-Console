<?php
	//
	// Fonctions utilitaires des chaînes de caractères.
	//

	//
	// Permet de mettre en majuscule la première lettre d'une phrase.
	//
	function capitalize(string $phrase): string
	{
		$first = mb_substr($phrase, 0, 1);	// Première lettre.
		$rest = mb_substr($phrase, 1);		// Suite de la chaîne.

		return mb_strtoupper($first) . $rest;
	}

	//
	// Permet de tenter la récupération d'une valeur inexistante et
	//	le retour d'une valeur de secours en cas d'échec.
	//
	function tryGetValue(string $phrase = "", mixed $fallback = ""): mixed
	{
		if (empty($phrase) || $phrase === "null")
		{
			return $fallback;
		}

		return $phrase;
	}
?>