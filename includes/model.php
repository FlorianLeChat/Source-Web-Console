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
		public PDO $connector;

		// Configuration générale du site.
		protected array $config;

		//
		// Permet d'initialiser certains mécanismes	lors de l'instanciation
		//	d'une des classes héritées du modèle principal.
		//
		public function __construct()
		{
			// Mise en mémoire de la configuration.
			$this->config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);

			// Connexion à la base de données.
			$this->getConnector();
		}

		//
		// Permet de récupérer une valeur de la configuration générale.
		//
		protected function getConfig(string $key): mixed
		{
			return $this->config[$key];
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
		// Permet de créer et de mettre en mémoire la connexion à
		//	la base de données SQL.
		//
		private function getConnector(): void
		{
			// On renseigne les informations de connexion.
			$link = sprintf("mysql:host=%s;dbname=%s;charset=%s;port=%s", $this->getConfig("sql_host"), $this->getConfig("sql_database"),
																			$this->getConfig("sql_charset"), $this->getConfig("sql_port"));

			// On définit ensuite les options de connexion.
			$options = [
				PDO::ATTR_ERRMODE			 	=> PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES 		=> false
			];

			// On tente enfin de créer la connexion avec les informations précédentes.
			try
			{
				$this->connector = new PDO($link, $this->getConfig("sql_username"), $this->getConfig("sql_password"), $options);
			}
			catch (PDOException $error)
			{
				throw new PDOException($error->getMessage(), (int)$error->getCode());
			}
		}
	}
?>