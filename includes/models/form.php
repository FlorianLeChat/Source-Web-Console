<?php
	//
	// Modèle de gestion des formulaires du site.
	//
	namespace Source\Models;

	// Classe permettant de gérer le formulaire de contact.
	final class Form extends Main
	{
		// Limites de caractères par champ.
		public array $length = [];

		// Outil de récupération des traductions.
		public Language $translation;

		//
		// Permet d'initialiser certains mécanismes lors de l'instanciation
		//	de la classe actuelle.
		//
		public function __construct()
		{
			// Exécution du constructeur parent.
			parent::__construct();

			// Initialisation du système des traductions.
			$this->translation = new Language();
		}

		//
		// Permet d'ajouter un message reçu depuis le formulaire de contact
		//	dans la base de données du site.
		//
		public function insertMessage(string $email, string $subject, string $content): void
		{
			$query = $this->connector->prepare("INSERT INTO contact (`email`, `subject`, `content`) VALUES (?, ?, ?);");
				$query->bindValue(1, $email);
				$query->bindValue(2, $subject);
				$query->bindValue(3, $content);
			$query->execute();
		}

		//
		// Permet de vérifier les dimensions d'une chaîne de caractères.
		//
		private function checkBounds(string $field, string $input): string
		{
			// On vérifie si le champ possède une limitation de caractères
			//	ou non (typiquement le sujet n'a pas besoin d'être contrôlé).
			$data = $this->length;

			if (array_key_exists($field, $data))
			{
				// On récupère la taille de l'entrée utilisateur ainsi
				//	que les tailles limites du champ.
				$data = $data[$field];
				$length = mb_strlen($input);

				if ($length < $data[0])
				{
					// La taille du champ est trop petite, on retourne
					//	une chaîne de caractères vides pour bloquer la
					//	validation dans l'étape suivante.
					return "";
				}
				elseif ($length > $data[1])
				{
					// La taille du champ est trop grand, on retourne
					//	la chaîne de caractères tronquée par rapport
					//	à la limite imposée.
					return mb_substr($input, 0, $data[1]);
				}
			}

			// Dans le dernier cas, on retourne juste la chaîne de
			//	caractères originale.
			return $input;
		}

		//
		// Permet de « rendre propre » des chaînes de caractères pour
		//	détecter les entrées invalides ou malveillantes.
		//
		public function serializeInput(string $field, string $input): string|false
		{
			// On convertit d'abord caractères spéciaux en balises
			//	HTML lisibles.
			$input = htmlentities($input, ENT_QUOTES);

			// On supprime les espaces en trop en début et à la fin de
			//	la chaîne de caractères.
			$input = trim($input);

			// On vérifie les dimensions de la chaîne de caractères.
			$input = $this->checkBounds($field, $input);

			// On vérifie après si le champ contenant une adresse électronique
			//	est considéré comme valide.
			//	Note : on regarde également si le nom de domaine de l'adresse
			//		est connu pour exister aux yeux du PHP.
			if (str_contains($field, "email"))
			{
				// Séparation du nom d'utilisateur et du nom de domaine.
				$domain = explode("@", $input)[1] ?? "invalid";

				if (!filter_var($input, FILTER_VALIDATE_EMAIL) || !checkdnsrr($domain, "MX"))
				{
					// Si le champ est invalide, on assigne une chaîne vide.
					$input = "";
				}
			}

			// On vérifie ensuite si le champ contenant une adresse IP de type 4
			//	ou 6 est valide.
			if (str_contains($field, "address") && !filter_var($input, FILTER_VALIDATE_IP))
			{
				// L'adresse IP est invalide, on assigne une chaîne vide.
				$input = "";
			}

			// Si la chaîne de caractères est vide, alors on retourne "false",
			//	dans le cas contraire, on retourne la chaîne modifiée précédemment.
			return tryGetValue($input, false);
		}

		//
		// Permet de remplacer certaines informations dans le message
		//	d'erreur qui doit être affiché à l'utilisateur si l'une
		//	des vérifications du formulaire échoue.
		//
		public function formatMessage(string $field): string
		{
			// On récupère d'abord la taille minimale et maximale du champ.
			$length = $this->length[$field];

			// On récupère ensuite le message d'erreur depuis la base de données.
			$message = $this->translation->getPhrase("form_client_check_failed");

			// On remplace alors les trois parties du message par les données.
			$message = str_replace("$1", $field, $message);			// Nom du champ.
			$message = str_replace("$2", $length[0], $message);		// Taille minimale.
			$message = str_replace("$3", $length[1], $message);		// Taille maximale.

			// On retourne enfin le message modifié.
			return $message;
		}
	}
?>