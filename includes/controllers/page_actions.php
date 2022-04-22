<?php
	//
	// Contrôleur de gestion de la page des actions et des commandes.
	//

	// On récupère tout d'abord les identifiants uniques concernant
	//	l'utilisateur et le serveur actuellement sélectionné.
	$user_id = $_SESSION["user_id"];
	$server_id = $_SESSION["server_id"] ?? 0;

	// On récupère ensuite les données actuelles du serveur si
	//	l'identifiant unique du serveur est valide.
	if (!empty($server_id))
	{
		$remote = $server->getServerData($user_id, $server_id);

		if (!empty($remote))
		{
			try
			{
				// Si les informations sont valides, on tente de se connecter
				//	au serveur avant de récupérer les règles définies.
				$server->connectServer($remote["admin_address"] ?? $remote["client_address"], $remote["admin_port"] ?? $remote["client_port"]);

				$rules = $server->query->GetRules();
			}
			catch (Exception $error)
			{
				// En cas d'erreur fatale, on l'ignore tout en continuant
				//	la suite du script.
			}
			finally
			{
				// À la fin de la requête, on libère la connexion réseau
				//	pour les autres scripts.
				$server->query->Disconnect();
			}
		}
	}

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// État actuel de la restriction de la lampe torche.
		"actions_value_flashlight" => $rules["mp_flashlight"] ?? "",

		// État actuel de la restriction des logiciels de triche.
		"actions_value_cheats" => $rules["sv_cheats"] ?? "0",

		// État actuel de la restriction des communications vocales.
		"actions_value_voice" => $rules["sv_voiceenable"] ?? "0",

		// Liste des commandes personnalisées.
		"actions_custom_commands" => $server->getCustomCommands($user_id)

	];
?>