<?php

//
// Service de gestion des serveurs de stockage.
//
namespace App\Service;

use FTP\Connection;
use App\Entity\Storage;
use phpseclib3\Net\SFTP;

class StorageManager
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
        $stream = null;
        $protocol = $storage->getProtocol();
        $password = $this->serverManager->decryptPassword((string) $storage->getPassword());

        // @codeCoverageIgnoreStart
        //  Note : l'extension FTP et un serveur FTP réel sont requis pour tester cette partie.
        if ($protocol === $storage::PROTOCOL_FTP)
        {
            // Protocole FTP chiffré (FTPS) : https://www.php.net/manual/fr/function.ftp-ssl-connect.php
            $connection = ftp_ssl_connect($storage->getAddress(), $storage->getPort());

            if ($connection && ftp_login($connection, $storage->getUsername(), $password))
            {
                // Activation du mode passif.
                ftp_pasv($connection, true);

                $stream = $connection;
            }
        }
        // @codeCoverageIgnoreEnd
        elseif ($protocol === $storage::PROTOCOL_SFTP)
        {
            // Protocole SFTP : https://phpseclib.com/docs/sftp
            $stream = new SFTP($storage->getAddress(), $storage->getPort());
            $stream->login($storage->getUsername(), $password);
        }

        // Connexion établie ou valeur nulle en cas d'échec ou de protocole inconnu.
        return $stream;
    }

    //
    // Récupère le contenu d'un fichier sur un serveur de stockage FTP
    //  sous une forme de chaîne de caractères.
    //
    public function getFileContents(mixed $stream, string $path): string
    {
        // @codeCoverageIgnoreStart
        //  Note : l'extension FTP et un serveur FTP réel sont requis pour tester cette partie.
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
        // @codeCoverageIgnoreEnd
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
        // @codeCoverageIgnoreStart
        //  Note : l'extension FTP et un serveur FTP réel sont requis pour tester cette partie.
        if ($stream instanceof Connection)
        {
            // Connexion FTP : https://www.php.net/manual/en/function.ftp-put
            ftp_put($stream, $path, $content);
        }
        // @codeCoverageIgnoreEnd
        elseif ($stream instanceof SFTP)
        {
            // Connexion SFTP : https://phpseclib.com/docs/sftp#uploading-files
            $stream->put($path, $content);
        }
    }
}
