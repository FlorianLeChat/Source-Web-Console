<?php
	//
	// Fonctions utilitaires des tableaux numériques/associatifs.
	//

	//
	// Permet de récupération tous les fichiers possibles d'un dossier.
	// 	Note : cette fonction applique automatiquement un correctif PHP.
	// 	Source : https://www.php.net/manual/fr/function.scandir.php#107215
	//
	function findFiles(string $directory): array|false
	{
		// On récupère tous les fichiers du répertoires.
		$files = scandir($directory);

		// On supprime ensuite les résultats invalides.
		$files = array_diff($files, array("..", "."));

		// On réarrange alors les indices du tableau.
		$files = array_values($files);

		// On retourne enfin la liste des résultats modifiés.
		return $files;
	}
?>