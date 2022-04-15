<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	final class Mail extends Main
	{
		// Adresse électronique d'émisssion.
		public const SOURCE_ADDRESS = "admin@florian-dev.fr";

		//
		// Permet de traiter les demandes de création d'un nouveau mot de passe
		// 	afin d'accéder à la page d'administration du site.
		//	Note : nécessite l'utilisation du serveur de production.
		// 	Source : https://www.cloudbooklet.com/how-to-install-and-setup-sendmail-on-ubuntu/
		//
		public function dispatch(string $address, string $subject, string $message): bool
		{
			// On vérifie si l'adresse du serveur actuel correspond à la machine
			//	de production chez l'hébergeur OVH.
			if (str_contains($_SERVER["SERVER_NAME"], "console.florian-dev.fr"))
			{
				// On indique que l'email n'a pas pu être envoyé.
				return false;
			}

			// Si c'est le cas, on envoie un email avec les informations renseignées.
			mb_send_mail(
				// Adresse électronique.
				$address,

				// Sujet du message.
				$subject,

				// Contenu du message.
				$message,

				// En-tête du message.
				[
					"From" => "Source Web Console <" . self::SOURCE_ADDRESS . ">",		// Auteur de l'email.
					"X-Mailer" => "PHP/" . phpversion()									// Serveur de messagerie.
				]
			);

			// On indique que l'email a été envoyé.
			return true;
		}
	}
?>