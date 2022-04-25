<?php
	// On initialise le contrôleur principal des données.
	require_once(__DIR__ . "/../controller.php");

	// On vérifie si l'utilisateur est actuellement connecté
	//	à un compte utilisateur.
	$user_id = $_SESSION["user_id"];

	if (empty($user_id))
	{
		// Indication : « Unauthorized ».
		// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/401
		http_response_code(401);
		exit();
	}

	// On vérifie si la page est demandée avec une requête AJAX.
	if (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")
	{
		// Si c'est le cas, on tente de récupérer le serveur sélectionné ainsi
		// 	que les données transmises dans la requête.
		$remote = $server->getServerData($user_id, $_SESSION["server_id"] ?? 0);
		$storage = $server->getExternalStorage($user_id, $remote["server_id"]);

		$action = $_POST["ftp_action"] ?? "";
		$address = $_POST["ftp_address"] ?? $storage["host"] ?? "";
		$port = $_POST["ftp_port"] ?? $storage["port"] ?? "";
		$protocol = $_POST["ftp_protocol"] ?? $storage["protocol"] ?? "";

		if (empty($remote) || empty($action) || empty($address) || empty($port) || empty($protocol))
		{
			// Indication : « Bad Request ».
			// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/400
			http_response_code(400);
			exit();
		}

		// On récupère les dernières informations tranmises dans la requête
		//	avant de déterminer l'action a réaliser.
		$username = $_POST["ftp_user"] ?? $storage["username"] ?? "";
		$password = $_POST["ftp_password"] ?? $server->decryptPassword($storage["password"]) ?? "";

		if ($action === "insert")
		{
			// Insertion des données dans la base de données.
			$server->addExternalStorage($remote["server_id"], $address, $port, $protocol, $username, $password);
		}
		elseif ($action === "update")
		{
			// Mise à jour des données dans la base de données.
			$server->updateExternalStorage($user_id, $address, $port, $protocol, $username, $password);
		}
		elseif ($action === "connexion_ftp")
		{
			// Connexion au serveur de stockage FTP.
		}
		elseif ($action === "connexion_sftp")
		{
			// Connexion au serveur de stockage SFTP.


			// $sftp = new SFTP("51.75.125.244", 27412);
			// $sftp->login("discretoss", "A3EN2c94iq");

			// $data = $sftp->get("serverfiles/garrysmod/cfg/gmodserver.cfg");
			// $data = str_replace("sv_region 0", "sv_region 1", $data);

			// echo($data);

			// $sftp->put("serverfiles/garrysmod/cfg/gmodserver.cfg", $data);
		}


		exit();
	}

	// Dans le cas contraire qu'il ne s'agit pas d'une requête AJAX,
	//	on signale à l'utilisateur la méthode n'est pas autorisée.
	// 	Source : https://developer.mozilla.org/fr/docs/Web/HTTP/Status/405
	http_response_code(405);
	exit();
?>