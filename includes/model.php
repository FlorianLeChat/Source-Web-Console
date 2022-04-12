<?php
	//
	// Modèle principal de la gestion des données.
	//
	namespace Source\Models;

	require_once(__DIR__ . "/models/language.php");

	use PDO;
	use PDOException;

	abstract class Main
	{
		// Connecteur à la base de données.
		protected PDO $connector;

		// Outil de récupération des traductions.
		public Language $translation;

		//
		// Permet d'initialiser certains mécanismes lors de l'instanciation
		//	d'une des classes héritées du modèle principal.
		//
		public function __construct()
		{
			// Connexion automatique à la base de données SQL.
			$this->getConnector();

			// Initialisation du système des sessions PHP.
			if (session_status() !== PHP_SESSION_ACTIVE)
			{
				session_start();
			}
		}

		//
		// Permet de définir et de récupérer la langue actuellement
		//	choisie par l'utilisateur.
		//
		public function setLanguage(string $code): void
		{
			$_SESSION["language"] = $code;
		}

		public function getLanguage(): string
		{
			return $_SESSION["language"];
		}

		//
		// Permet de mettre en majuscule la première lettre d'une phrase.
		//
		public function capitalize(string $phrase): string
		{
			$first = mb_substr($phrase, 0, 1);	// Première lettre.
			$rest = mb_substr($phrase, 1);		// Suite de la chaîne.

			return mb_strtoupper($first) . $rest;
		}

		//
		// Permet de créer et de mettre en mémoire la connexion à
		//	la base de données SQL.
		//
		private function getConnector(): void
		{
			// On renseigne les informations de connexion.
			$credentials = fgetcsv(fopen(__DIR__ . "/../config.csv", "r"));
			$link = sprintf("mysql:host=%s;dbname=%s;charset=%s;port=%s", $credentials[0], $credentials[1], $credentials[4], $credentials[5]);

			// On définit ensuite les options de connexion.
			$options = [
				PDO::ATTR_ERRMODE			 	=> PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES 		=> false,
			];

			// On tente enfin de créer la connexion avec les informations précédentes.
			try
			{
				$this->connector = new PDO($link, $credentials[2], $credentials[3], $options);
			}
			catch (PDOException $error)
			{
				throw new PDOException($error->getMessage(), (int)$error->getCode());
			}
		}
	}
?>