<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	require_once(__DIR__ . "/../model.php");

	final class Server extends Main
	{
		//
		// Permet d'enregistrer une nouvelle instance dans la base de données.
		//
		public function storePublicInstance(int $identifier, string $address, string $port, bool $secure = false, bool $auto_connect = false)
		{
			$query = $this->connector->prepare("INSERT INTO servers (`client_id`, `client_address`, `client_port`, `secure_only`, `auto_connect`) VALUES (?, ?, ?, ?, ?);");
				$query->bindValue(1, $identifier);
				$query->bindValue(2, $address);
				$query->bindValue(3, $port);
				$query->bindValue(4, intval($secure));
				$query->bindValue(5, intval($auto_connect));
			$query->execute();
		}

		//
		// Permet d'enregistrer les informations de connexion au système d'administration
		//	dans la base de données.
		//
		public function storeAdminCredentials(int $identifier, string $address, string $port, string $password)
		{
			$query = $this->connector->prepare("UPDATE servers SET `admin_address` = ?, `admin_port` = ?, `admin_password` = ? WHERE `server_id` = ?");
				$query->bindValue(1, $address);
				$query->bindValue(2, $port);
				$query->bindValue(3, $password);
				$query->bindValue(4, $identifier);
			$query->execute();
		}
	}
?>