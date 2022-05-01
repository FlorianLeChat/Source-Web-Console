<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	use PHPMailer\PHPMailer\PHPMailer;

	final class Mail extends Main
	{
		//
		// Permet de traiter les demandes de création d'un nouveau mot de passe
		// 	afin d'accéder à la page d'administration du site.
		//	Note : nécessite l'utilisation du serveur de production.
		//
		public function dispatch(string $address, string $subject, string $message): bool
		{
			// On vérifie si l'adresse du serveur actuel correspond à la machine
			//	de production chez l'hébergeur OVH.
			if ($_SERVER["SERVER_NAME"] !== "console.florian-dev.fr")
			{
				// On indique que l'email n'a pas pu être envoyé.
				return false;
			}

			// On créé alors une nouvelle instance pour envoyer un email.
			$mail = new PHPMailer();

			// Paramètres généraux.
			$mail->isSMTP();
			$mail->CharSet = "UTF-8";
			$mail->Host = $this->getConfig("smtp_host");
			$mail->SMTPAuth = true;
			$mail->Username = $this->getConfig("smtp_username");
			$mail->Password = $this->getConfig("smtp_password");
			$mail->Port = $this->getConfig("smtp_port");

			// Envoyeur/destinataire de l'email.
			$mail->setFrom($this->getConfig("smtp_username"), "Source Web Console");
			$mail->addAddress($address);

			// Paramètres DKIM.
			// 	Source : https://github.com/PHPMailer/PHPMailer/blob/bf99c202a92daa6d847bc346d554a4727fd802a5/examples/DKIM_sign.phps
			$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
			$mail->DKIM_domain = $this->getConfig("dkim_domain");
			$mail->DKIM_private = $this->getConfig("dkim_private_key");
			$mail->DKIM_selector = $this->getConfig("dkim_selector");
			$mail->DKIM_identity = $mail->From;
			$mail->DKIM_copyHeaderFields = false;

			// Contenu du message.
			$mail->Subject = $subject;
			$mail->Body = $message;
			$mail->AltBody = $mail->Body;

			// On retourne enfin l'état d'envoi de l'email.
			return $mail->send();
		}
	}
?>