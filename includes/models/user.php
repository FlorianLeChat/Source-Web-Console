<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	final class User extends Main
	{
		// Temps d'expiration du jeton d'authentification (en secondes).
		public const EXPIRATION_TIME = 60 * 60 * 24 * 31;

		//
		// Permet de traiter les demandes de création d'un nouveau mot de passe
		// 	afin d'accéder à la page d'administration du site.
		//
		public function createNewPassword(string $username, string $password): void
		{
			// On effectue une requête pour vérifier si le nom d'utilisateur
			//	renseignée est présent dans la base de données.
			$query = $this->connector->prepare("SELECT 1 FROM `users` WHERE `username` = ?;");
				$query->bindValue(1, $username);
			$query->execute();

			$result = $query->fetch();

			// On vérifie alors si le nom d'utilisateur est présente dans le
			//	résultat de la requête SQL.
			if (is_array($result) && count($result) > 0)
			{
				// Si c'est le cas, on "hash" le nouveau mot de passe avant de l'insérer.
				$query = $this->connector->prepare("UPDATE `users` SET `password` = ? WHERE `username` = ?;");
					$query->bindValue(1, password_hash($password, PASSWORD_DEFAULT));
					$query->bindValue(2, $username);
				$query->execute();

				// On supprime le jeton d'authentification par la même occasion.
				$this->storeToken(username: $username);
			}
		}

		//
		// Permet de générer un nouveau jeton d'authentification avant de
		//	l'enregistrer automatiquement dans la base de données.
		//
		public function generateToken()
		{
			$this->storeToken(bin2hex(random_bytes(32)));
		}

		//
		// Permet de comparer et de valider un jeton d'authentification
		//	envoyé par un utilisateur connecté précédemment.
		//
		public function compareToken(string $token): bool
		{
			// On exécute une requête SQL pour récupérer le jeton
			//	d'authentification enregistré dans la base de données.
			$query = $this->connector->prepare("SELECT `client_id`, `username`, `creation_time`, `level` FROM `users` WHERE `access_token` = ?;");
				$query->bindValue(1, $token);
			$query->execute();

			$result = $query->fetch();

			// On vérifie alors le résultat de la requête.
			if (is_array($result) && count($result) > 0 && strtotime($result["creation_time"]) + self::EXPIRATION_TIME > time())
			{
				// Si elle est valide, on met en mémoire certaines
				//	informations avant d'indiquer que l'authentification
				//	avec le jeton a réussie.
				$_SESSION["user_id"] = $result["client_id"];
				$_SESSION["user_name"] = $result["username"];
				$_SESSION["user_level"] = $result["level"];

				return true;
			}

			// Dans le cas contraire, on signale que le jeton est invalide.
			return false;
		}

		//
		// Permet d'enregistrer le jeton d'authentification de l'utilisateur
		//	dans la base de données.
		//
		public function storeToken(string $token = "", string $username = null): void
		{
			// On détermine si l'horodatage présent dans la base de données
			// 	doit être actualisé ou non (uniquement lors d'une connexion).
			$timestamp = empty($token) ? "`creation_time`" : "NULL";

			// On effectue juste après la requête de mise à jour.
			$query = $this->connector->prepare("UPDATE `users` SET `access_token` = ?, `creation_time` = $timestamp WHERE `username` = ?;");
				$query->bindValue(1, $token);
				$query->bindValue(2, $username ?? $_SESSION["user_name"] ?? $this->connector->lastInsertId());
			$query->execute();

			// On définit enfin le cookie de mise à jour pour le client.
			// 	Note : cette définition est faite uniquement lorsque le trafic
			//		du site est entièrement sécurisé.
			if (isset($_SERVER["HTTPS"]))
			{
				// Si le jeton est vide, alors le cookie doit être supprimé.
				setcookie("generated_token", $token, empty($token) ? 1 : time() + self::EXPIRATION_TIME, "/", $_SERVER["HTTP_HOST"], true);
			}
		}

		//
		// Permet de vérifier si un nom d'utilisateur n'est pas déjà enregistré
		//	dans la base de données.
		//
		private function checkDuplication(string $username): bool
		{
			// On effectue d'abord une recherche dans la base de données en recherchant
			//	un enregistrement avec le nom d'utilisateur renseigné.
			$query = $this->connector->prepare("SELECT 1 FROM `users` WHERE `username` = ?;");
				$query->bindValue(1, $username);
			$query->execute();

			$result = $query->fetch();

			// On retourne alors une valeur booléenne en fonction du résultat.
			return is_array($result) && count($result) > 0;
		}

		//
		// Permet de créer un nouveau compte utilisateur dans a base de données.
		//
		public function register(string $username, string $password): bool
		{
			// On vérifie tout d'abord si le nom d'utilisateur existe déjà ou non
			//	dans la base de données.
			if ($this->checkDuplication($username))
			{
				// On retourne alors une valeur booléenne pour signifier que
				//	l'inscription a échouée.
				return false;
			}

			// Si ce n'est pas le cas, on réalise ensuite une requête classique pour
			//	insérer les informations dans la base de données.
			$query = $this->connector->prepare("INSERT INTO users (`username`, `password`) VALUES (?, ?);");
				$query->bindValue(1, $username);
				$query->bindValue(2, password_hash($password, PASSWORD_DEFAULT));
			$query->execute();

			// On déconnecte après explicitement l'ancien compte utilisateur.
			//	Note : cela est utile pour détruire le mécanisme de maintien de la
			//		connexion à travers le temps.
			$this->destroy();

			// On procède également à la connexion automatique de l'utilisateur.
			$this->authenticate($username, $password);

			// On fait en sorte de garder l'utilisateur connecté sur le long terme
			//	pour éviter toute reconnexion après son départ du site.
			$this->generateToken();

			// On retourne enfin une valeur booléenne pour indiquer l'inscription
			//	s'est déroulée avec succès.
			return true;
		}

		//
		// Permet de mettre à jour les identifiants de connexion actuellement
		//	enregistrés dans la base de données.
		//
		public function update(?string $username, ?string $password): void
		{
			if (!empty($username))
			{
				// Mise à jour du nom d'utilisateur.
				$query = $this->connector->prepare("UPDATE `users` SET `username` = ? WHERE `client_id` = ?;");
					$query->bindValue(1, $username);
					$query->bindValue(2, $_SESSION["user_id"]);
				$query->execute();

				// Mise à jour des données en session.
				$_SESSION["user_name"] = $username;
			}

			if (!empty($password))
			{
				// Mise à jour du mot de passe.
				$query = $this->connector->prepare("UPDATE `users` SET `password` = ? WHERE `client_id` = ?;");
					$query->bindValue(1, password_hash($password, PASSWORD_DEFAULT));
					$query->bindValue(2, $_SESSION["user_id"]);
				$query->execute();
			}
		}

		//
		// Permet d'authentifier un utilisateur au niveau de la	base de données.
		//
		public function authenticate(string $username, string $password): bool
		{
			// On effectue d'abord une requête SQL pour vérifier si un enregistrement
			//	est présent avec les identifiants donnés lors de l'étape précédente.
			$query = $this->connector->prepare("SELECT `client_id`, `password`, `level` FROM `users` WHERE `username` = ?;");
				$query->bindValue(1, $username);
			$query->execute();

			$result = $query->fetch();

			// On vérifie ensuite le résultat de la requête SQL avant
			//	de comparer le mot de passe hashé par l'entrée utilisateur.
			if (is_array($result) && count($result) > 0 && password_verify($password, $result["password"]))
			{
				// On retourne enfin une valeur booléenne indiquant que
				//	l'authentification a réussie.
				$_SESSION["user_id"] = $result["client_id"];
				$_SESSION["user_name"] = $username;
				$_SESSION["user_level"] = $result["level"];

				return true;
			}

			// Dans le cas contraire, on retourne une valeur pour signifier
			//	que l'authentification a échouée.
			return false;
		}

		//
		// Permet de supprimer l'utilisateur ainsi que toutes ses données
		//	associées dans la base de données.
		//
		public function remove(): void
		{
			// Suppression du compte utilisateur.
			$query = $this->connector->prepare("DELETE FROM `users` WHERE `client_id` = ?;");
				$query->bindValue(1, $_SESSION["user_id"]);
			$query->execute();

			// Suppression des serveurs enregistrés.
			$query = $this->connector->prepare("DELETE FROM `servers` WHERE `client_id` = ?;");
				$query->bindValue(1, $_SESSION["user_id"]);
			$query->execute();
		}

		//
		// Permet de déconnecter l'utilisateur actuellement connecté.
		//
		public function destroy(): void
		{
			// On supprime le jeton d'authentification de l'utilisateur.
			$this->storeToken();

			// On supprime certaines informations utilisateurs sauvegardées
			// 	dans les sessions.
			unset($_SESSION["user_id"]);
			unset($_SESSION["user_name"]);
			unset($_SESSION["server_id"]);
		}
	}
?>