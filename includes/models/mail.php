<?php
	//
	// Contrôleur de gestion des données utilisateurs.
	//
	namespace Source\Models;

	use SMTPValidateEmail\Validator;
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
			// On vérifie si l'adresse du serveur actuel correspond à l'environnement
			// 	de développement ou la machine de production chez l'hébergeur OVH.
			if ($_SERVER["SERVER_NAME"] === "localhost")
			{
				return false;
			}

			// On vérifie alors si l'adresse du destinataire existe bien.
			$validator = new Validator($address, $this->getConfig("SMTP", "username"));
			$results = $validator->validate();

			if (array_values($results)[0] === false)
			{
				return false;
			}

			// On créé ensuite une nouvelle instance pour envoyer un email.
			$mail = new PHPMailer();

			// Paramètres généraux.
			$mail->isSMTP();
			$mail->CharSet = "UTF-8";
			$mail->Host = $this->getConfig("SMTP", "host");
			$mail->SMTPAuth = true;
			$mail->Username = $this->getConfig("SMTP", "username");
			$mail->Password = $this->getConfig("SMTP", "password");
			$mail->Port = $this->getConfig("SMTP", "port");

			// Envoyeur/destinataire de l'email.
			$mail->setFrom($this->getConfig("SMTP", "username"), "Source Web Console");
			$mail->addAddress($address);

			// Paramètres DKIM.
			// 	Source : https://github.com/PHPMailer/PHPMailer/blob/bf99c202a92daa6d847bc346d554a4727fd802a5/examples/DKIM_sign.phps
			$mail->DKIM_domain = $this->getConfig("DKIM", "domain");
			$mail->DKIM_private = $this->getConfig("DKIM", "private_key");
			$mail->DKIM_selector = $this->getConfig("DKIM", "selector");
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