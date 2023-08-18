<?php

//
// Service de gestion des serveurs de stockage.
//
namespace App\Service;

use FTP\Connection;
use App\Entity\Storage;
use phpseclib3\Net\SFTP;

final class StorageManager
{
	//
	// Initialisation de certaines dépendances du service.
	//
	public function __construct(private readonly ServerManager $serverManager) {}

	//
	// Initialise une connexion FTP vers le serveur de stockage d'un serveur.
	//
	public function openConnection(Storage $storage): mixed
	{
		$protocol = $storage->getProtocol();

		if ($protocol === $storage::PROTOCOL_FTP)
		{
			// Protocol FTP : https://www.php.net/manual/fr/function.ftp-connect.php
			$stream = ftp_connect($storage->getAddress(), $storage->getPort());

			if (ftp_login($stream, $storage->getUsername(), $this->serverManager->decryptPassword($storage->getPassword())))
			{
				// Activation du mode passif.
				ftp_pasv($stream, true);

				// Connexion réussie.
				return $stream;
			}

			// Échec de la connexion.
			return null;
		}
		elseif ($protocol === $storage::PROTOCOL_SFTP)
		{
			// Protocole SFTP : https://phpseclib.com/docs/sftp
			$stream = new SFTP($storage->getAddress(), $storage->getPort());
			$stream->login($storage->getUsername(), $this->serverManager->decryptPassword($storage->getPassword()));

			return $stream;
		}
	}

	//
	// Récupère le contenu d'un fichier sur un serveur de stockage FTP
	//  sous une forme de chaîne de caractères.
	//
	public function getFileContents(mixed $stream, string $path): string
	{
		if ($stream instanceof Connection)
		{
			// Connexion FTP : https://www.php.net/manual/en/function.ftp-fget.php#86107
			$handler = fopen("php://temp", "r+");

			if (ftp_fget($stream, $handler, $path, FTP_ASCII))
			{
				// Transformation d'une référence de variable en pointer.
				rewind($handler);

				// Récupération du contenu du fichier.
				$output = stream_get_contents($handler);
			}
		}
		elseif ($stream instanceof SFTP)
		{
			// Connexion SFTP : https://phpseclib.com/docs/sftp#downloading-files
			$output = $stream->get($path);
		}

		return $output ?? "";
	}

	//
	// Téléverse le contenu d'un fichier sur un serveur de stockage.
	//
	public function putFileContents(mixed $stream, string $path, string $content): void
	{
		if ($stream instanceof Connection)
		{
			// Connexion FTP : https://www.php.net/manual/en/function.ftp-put
			ftp_put($stream, $path, $content);
		}
		elseif ($stream instanceof SFTP)
		{
			// Connexion SFTP : https://phpseclib.com/docs/sftp#uploading-files
			$stream->put($path, $content);
		}
	}
}