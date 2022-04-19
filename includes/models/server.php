<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	require __DIR__ . "/../../vendor/xpaw/php-source-query-class/SourceQuery/bootstrap.php";

	use xPaw\SourceQuery\SourceQuery;

	final class Server extends Main
	{
		// Connecteur Source RCON Query.
		public SourceQuery $query;

		// Méthode de chiffrement pour le mot de passe administrateur.
		private const ENCRYPTION_METHOD = "AES-256-CTR";

		//
		// Permet d'initialiser certains mécanismes lors de l'instanciation
		//	de la classe actuelle.
		//
		public function __construct()
		{
			// Exécution du constructeur parent.
			parent::__construct();

			// Initialisation de la connexion administrateur à l'instance.
			$this->query = new SourceQuery();
		}

		//
		// Permet d'établir une connexion avec une instance distante.
		//
		public function connectInstance(string $address, int $port, string $password = "")
		{
			// On établit la connexion avec les informations renseignées.
			$this->query->Connect($address, $port, 1, SourceQuery::SOURCE);

			// On vérifie alors si le mot de passe administrateur est indiqué.
			if (!empty($password))
			{
				// On déchiffre enfin le mot de passe avant de le définir.
				$this->query->SetRconPassword($this->password_decrypt($password));
			}
		}

		//
		// Permet de chiffrer une chaîne de caractères en utilisant les fonctions
		//	de la bibliothèque de OpenSSL.
		// 	Source : https://stackoverflow.com/a/60283328
		//
		private function password_encrypt(string $password): string
		{
			// On chiffre d'abord la phrase unique de chiffrement.
			$key = hash("sha256", $this->getConfig("openssl_phrase"));

			// On chiffre ensuite le vecteur d'initialisation.
			$iv = substr(hash("sha256", $this->getConfig("openssl_iv")), 0, 16);

			// On utilise après la fonction de OpenSSL pour chiffrer
			//	à deux reprises le mot de passe.
			$password = openssl_encrypt($password, self::ENCRYPTION_METHOD, $key, 0, $iv);
			$password = base64_encode($password);

			// On retourne enfin le mot de passe chiffré.
			return $password;
		}

		//
		// Permet de dechiffrer une chaîne de caractères en utilisant les fonctions
		//	de la bibliothèque de OpenSSL.
		// 	Source : voir fonction précédente.
		//
		private function password_decrypt(string $password): bool
		{
			// On chiffre d'abord la phrase unique de chiffrement.
			$key = hash("sha256", $this->getConfig("openssl_phrase"));

			// On chiffre ensuite le vecteur d'initialisation.
			$iv = substr(hash("sha256", $this->getConfig("openssl_iv")), 0, 16);

			// On utilise après la fonction de OpenSSL pour déchiffrer
			//	complétement le mot de passe.
			$password = base64_decode($password);
			$password = openssl_decrypt($password, self::ENCRYPTION_METHOD, $key, 0, $iv);

			// On retourne enfin le mot de passe déchiffré.
			return $password;
		}

		//
		// Permet d'enregistrer une nouvelle instance dans la base de données.
		//
		public function storePublicInstance(int $identifier, string $address, string $port, bool $secure = false, bool $auto_connect = false): void
		{
			$query = $this->connector->prepare("INSERT INTO servers (`client_id`, `client_address`, `client_port`, `game_platform`, `secure_only`, `auto_connect`) VALUES (?, ?, ?, ?, ?, ?);");

				// Identifiant unique du client.
				$query->bindValue(1, $identifier);

				// Adresse IP du serveur.
				$query->bindValue(2, $address);

				// Port de communication du serveur.
				$query->bindValue(3, $port);

				// Jeu utilisé sur la plate-forme Steam.
				$query->bindValue(4, $this->getGameIDByAddress($address));

				// Option de maintien de connexion sous trafic sécurisé (HTTPS).
				$query->bindValue(5, intval($secure));

				// Option de connexion automatique lors de l'arrivée sur le
				//	tableau de bord.
				$query->bindValue(6, intval($auto_connect));

			$query->execute();
		}

		//
		// Permet d'enregistrer les informations de connexion au système d'administration
		//	dans la base de données.
		//
		public function storeAdminCredentials(int $identifier, string $address, string $port, string $password): void
		{
			$query = $this->connector->prepare("UPDATE servers SET `admin_address` = ?, `admin_port` = ?, `admin_password` = ? WHERE `server_id` = ?");

				// Adresse IP du module d'administration (RCON).
				// 	Source : https://developer.valvesoftware.com/wiki/Source_RCON_Protocol#Requests_and_Responses
				$query->bindValue(1, $address);

				// Port de communication du module.
				$query->bindValue(2, $port);

				// Mot de passe chiffré pour l'accès au module.
				$query->bindValue(3, $this->password_encrypt($password));

				// Identifiant unique du serveur.
				$query->bindValue(4, $identifier);

			$query->execute();
		}

		//
		// Permet de déterminer le nom complet du jeu utilisé par une instance à partir
		//	de sa plate-forme ou son numéro d'identification unique.
		//	Source : https://github.com/BrakeValve/dataflow/issues/5 (non officielle)
		//
		public function getNameByGameID(int $identifier, string $fallback = ""): string
		{
			// On fait une requête à l'API centrale Steam pour récupérer
			//	les informations du magasin.
			$response = file_get_contents("https://store.steampowered.com/api/appdetails?appids=$identifier");

			// On transforme par la suite ce résultat sous format JSON en
			//	tableau associatif pour la manipuler.
			$response = json_decode($response, true);

			if (is_array($response) && count($response) > 0)
			{
				// Si la réponse semble correcte, on vérifie si l'API indique
				//	que la réponse est un succès et s'il existe les informations
				// 	attendues initialement.
				$response = $response[$identifier];

				if ($response["success"] === false)
				{
					// Si ce n'est pas le cas, on renvoie juste la valeur
					//	de secours.
					return $fallback;
				}

				// Dans le cas contraire, on retourne le nom du jeu comme attendu.
				return $response["data"]["name"];
			}

			// On retourne enfin le résultat par défaut si la demande a échouée.
			return $fallback;
		}

		//
		// Permet de récupérer l'identifiant unique du jeu actuellement monté sur le
		//	serveur dédié de l'instance du client à partir de son adresse IP.
		// 	Source : https://partner.steamgames.com/doc/webapi/ISteamApps#GetServersAtAddress
		//
		private function getGameIDByAddress(string $address): int
		{
			// On fait une requête à l'API centrale Steam pour récupérer
			//	les informations de l'instance.
			$response = file_get_contents("https://api.steampowered.com/ISteamApps/GetServersAtAddress/v1/?addr=$address");

			// On transforme par la suite ce résultat sous format JSON en
			//	tableau associatif pour la manipuler.
			$response = json_decode($response, true);

			if (is_array($response) && count($response) > 0)
			{
				// Si la réponse semble correcte, on vérifie si l'API indique
				//	que la réponse est un succès et s'il existe une liste
				//	d'informations comme attendu.
				$response = $response["response"];

				if ($response["success"] === false || count($response["servers"]) === 0)
				{
					// Si ce n'est pas le cas, on renvoie juste que le serveur
					//	utilise un jeu qui n'est pas sur la plate-forme Steam.
					return 0;
				}

				// Dans le cas contraire, on retourne l'identifiant du jeu comme attendu.
				return $response["servers"][0]["appid"];
			}

			// On retourne enfin un résultat par défaut si la requête a échouée
			//	quelque part (connexion Internet impossible, API hors service...).
			return 0;
		}

		//
		// Permet de récupérer toutes les instances enregistrés d'un compte utilisateur.
		//
		public function getInstances(int $identifier): array|false
		{
			// On récupère d'abord tous les potentiels serveurs dans la base de données.
			$query = $this->connector->prepare("SELECT * FROM servers WHERE client_id = ?");
				$query->bindValue(1, $identifier);
			$query->execute();

			$result = $query->fetchAll();

			// En fonction du résultat, on retourne alors les résultats.
			if (is_array($result) && count($result) > 0)
			{
				// Résultats trouvés.
				return $result;
			}

			// Indication de l'échec de la récupération.
			return false;
		}
	}
?>