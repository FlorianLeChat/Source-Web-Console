<?php

namespace App\Service;

use FTP\Connection;
use App\Entity\Server;
use App\Entity\Storage;
use phpseclib3\Net\SFTP;
use xPaw\SourceQuery\SourceQuery;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

readonly class ServerManager
{
	// Phrase unique pour le chiffrement symétrique.
	private string $sslPhrase;

	// Durée du cache pour les informations du serveur.
	private const CACHE_LIFETIME = 3600 * 24 * 7;

	// Méthode de chiffrement pour le mot de passe administrateur.
	private const ENCRYPTION_METHOD = "AES-256-CTR";

	//
	// Initialisation de certaines variables du service.
	//
	public function __construct(
		public SourceQuery $query,
		private CacheInterface $cache,
		private HttpClientInterface $client,
		private ContainerBagInterface $parameters,
	) {
		$this->sslPhrase = $this->parameters->get("app.ssl_phrase");
	}

	//
	// Établissement de la connexion avec le serveur de jeu.
	//
	public function connect(Server $server)
	{
		// On établit la connexion avec les informations renseignées.
		$this->query->Connect($server->getAddress(), $server->getPort(), 1, SourceQuery::SOURCE);

		// On vérifie alors si le mot de passe administrateur est indiqué.
		if (!empty($password = $server->getPassword()))
		{
			// On déchiffre enfin le mot de passe avant de le définir.
			$this->query->SetRconPassword($this->decryptPassword($password));
		}
	}

	//
	// Chiffre symétriquement une chaîne de caractères en utilisant les fonctions
	//  de la bibliothèque de OpenSSL.
	// 	Source : https://www.php.net/manual/en/function.openssl-encrypt
	//
	public function encryptPassword(string $password): string
	{
		// On génère d'abord le vecteur d'initialisation.
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));

		// On utilise ensuite la fonction de OpenSSL pour chiffrer
		//	le mot de passe avec la phrase unique ainsi que le
		//	vecteur d'initialisation créés précédemment.
		$password = openssl_encrypt($password, self::ENCRYPTION_METHOD, $this->sslPhrase, OPENSSL_RAW_DATA, $iv);

		// On utilise après la fonction pour créer une clé de hachage
		// 	en utilisant la méthode HMAC.
		$hmac = hash_hmac("sha256", $password, $this->sslPhrase, true);

		// On retourne enfin le mot de passe chiffré.
		return base64_encode($iv . $hmac . $password);
	}

	//
	// Déchiffre symétriquement une chaîne de caractères en utilisant les fonctions
	//  de la bibliothèque de OpenSSL.
	// 	Source : https://www.php.net/manual/en/function.openssl-decrypt.php
	//
	private function decryptPassword(string $password): string
	{
		// On décode d'abord le mot de passe chiffré.
		$password = base64_decode($password);

		// On détermine la longueur du vecteur d'initialisation.
		$length = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);

		// On récupère la longueur du vecteur d'initialisation.
		$iv = substr($password, 0, $length);

		// On récupère la longueur du mot de passe chiffré.
		$hmac = substr($password, $length, 32);

		// On récupère alors une partie du mot de passe chiffré.
		$password = substr($password, $length + 32);

		// On récupère ensuite le mot de passe déchiffré.
		$plain_text = openssl_decrypt($password, self::ENCRYPTION_METHOD, $this->sslPhrase, OPENSSL_RAW_DATA, $iv);

		// On vérifie après si le mot de passe est valide.
		$calcmac = hash_hmac("sha256", $password, $this->sslPhrase, true);

		// On vérifie enfin si les deux clés de hachage sont identiques.
		// 	Note : c'est une protection contre les attaques temporelles.
		//	Source : https://en.wikipedia.org/wiki/Timing_attack
		return hash_equals($hmac, $calcmac) ? $plain_text : "";
	}

	//
	// Détermine le nom complet du jeu utilisé par un serveur à partir de sa
	//  plate-forme ou son numéro d'identification unique.
	//	Source : https://github.com/BrakeValve/dataflow/issues/5 (non officielle)
	//
	public function getNameByGameID(int $identifier, string $fallback = ""): string
	{
		// On vérifie d'abord si le nom du jeu est déjà enregistré
		// 	dans le cache de données.
		return $this->cache->get("svc_game_$identifier", function (ItemInterface $item) use ($identifier, $fallback): string
		{
			// Si ce n'est pas le cas, on définit une durée de vie
			// 	de persistance pour le cache.
			$item->expiresAfter(self::CACHE_LIFETIME);

			// On fait une requête à l'API Steam pour récupérer
			//  les informations nécessaires.
			$response = $this->client->request("GET",
				sprintf("https://store.steampowered.com/api/appdetails?appids=%s", $identifier)
			);

			// On vérifie après si la requête a réussie ou non.
			if ($response->getStatusCode() !== 200)
			{
				// Si la requête a échouée, on renvoie alors la valeur
				//	de secours.
				return $fallback;
			}

			// On transforme par la suite ce résultat sous format JSON
			//	en tableau associatif pour le manipuler.
			$response = json_decode($response->getContent(), true);

			if (is_array($response) && count($response) > 0)
			{
				// Si la réponse semble correcte, on vérifie si l'API indique
				//	que la réponse est un succès et s'il existe les informations
				// 	attendues initialement.
				$response = $response[$identifier];

				if ($response["success"] === false)
				{
					// Si l'API indique que la réponse est un échec, on renvoie
					// 	également la valeur de secours.
					return $fallback;
				}

				// Sinon, on retourne tout simplement le nom du jeu.
				return $response["data"]["name"];
			}

			// On retourne enfin le résultat par défaut si la requête a échouée
			//	quelque part ou si la réponse n'est pas conforme.
			return $fallback;
		});
	}

	//
	// Récupère l'identifiant unique du jeu actuellement monté sur le serveur de jeu
	//  du client à partir de son adresse IP.
	// 	Source : https://partner.steamgames.com/doc/webapi/ISteamApps#GetServersAtAddress
	//
	public function getGameIDByAddress(string $address, int $port): int
	{
		// On vérifie d'abord si le nom du jeu est déjà enregistré
		// 	dans le cache de données.
		return $this->cache->get("swc_server_$address-$port", function (ItemInterface $item) use ($address, $port): int
		{
			// Si ce n'est pas le cas, on définit une durée de vie
			// 	de persistance pour le cache.
			$item->expiresAfter(self::CACHE_LIFETIME);

			// On fait une requête à l'API Steam pour récupérer
			//  les informations nécessaires.
			$response = $this->client->request("GET",
				sprintf("https://api.steampowered.com/ISteamApps/GetServersAtAddress/v1/?addr=%s:%d", $address, $port)
			);

			// On vérifie après si la requête a réussie ou non.
			if ($response->getStatusCode() !== 200)
			{
				// Si ce n'est pas le cas, on renvoie juste que le serveur
				//	utilise un jeu qui n'est pas sur la plate-forme Steam.
				return 0;
			}

			// On transforme par la suite ce résultat sous format JSON
			//	en tableau associatif pour le manipuler.
			$response = json_decode($response->getContent(), true);

			if (is_array($response) && count($response) > 0)
			{
				// Si la réponse semble correcte, on vérifie si l'API indique
				//	que la réponse est un succès et s'il existe une liste
				//  d'informations comme attendu.
				$response = $response["response"];

				if ($response["success"] === false || count($response["servers"]) === 0)
				{
					// Si l'API indique que la réponse est un échec, on renvoie également
					//  que le serveur utilise un jeu n'étant pas sur la plate-forme Steam.
					return 0;
				}

				// Dans le cas contraire, on retourne l'identifiant du jeu.
				return $response["servers"][0]["appid"];
			}

			// On retourne enfin le résultat par défaut si la requête a échouée
			//	quelque part ou si la réponse n'est pas conforme.
			return 0;
		});
	}

	//
	// Initialise une connexion FTP vers le serveur de stockage d'un serveur.
	//
	public function openFTPConnection(Storage $storage): mixed
	{
		$protocol = $storage->getProtocol();

		if ($protocol === $storage::PROTOCOL_FTP)
		{
			// Protocol FTP : https://www.php.net/manual/fr/function.ftp-connect.php
			$stream = ftp_connect($storage->getAddress(), $storage->getPort());

			if (ftp_login($stream, $storage->getUsername(), $this->decryptPassword($storage->getPassword())))
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
			$stream->login($storage->getUsername(), $this->decryptPassword($storage->getPassword()));

			return $stream;
		}
	}

	//
	// Récupère le contenu d'un fichier sur un serveur de stockage FTP
	//  sous une forme de chaîne de caractères.
	//
	public function getFTPFileContents(mixed $stream, string $path): string
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
	public function putFTPFileContents(mixed $stream, string $path, string $content): void
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