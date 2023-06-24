<?php

namespace App\Service;

use xPaw\SourceQuery\SourceQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ServerManager
{
	// Phrase unique pour le chiffrement symétrique.
	private $sslPhrase;

	// Méthode de chiffrement pour le mot de passe administrateur.
	private const ENCRYPTION_METHOD = "AES-256-CTR";

	//
	// Initialisation de certaines variables du service.
	//
    public function __construct(public SourceQuery $query, private ContainerBagInterface $parameters)
	{
		$this->query = $query;
		$this->sslPhrase = $this->parameters->get("app.ssl_phrase");
	}

	//
	// Établissement de la connexion avec le serveur de jeu.
	//
	public function connect(string $address, int $port, string $password = ""): void
	{
		// On établit la connexion avec les informations renseignées.
		$this->query->Connect($address, $port, 1, SourceQuery::SOURCE);

		// On vérifie alors si le mot de passe administrateur est indiqué.
		if (!empty($password))
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
	public function decryptPassword(string $password): string
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
		// On fait une requête à l'API centrale Steam pour récupérer
		//	les informations du magasin.
		$response = file_get_contents("https://store.steampowered.com/api/appdetails?appids=$identifier");

		// On transforme par la suite ce résultat sous format JSON en
		//	tableau associatif pour la manipuler.
		$response = json_decode($response, true);

		if (is_array($response) && count($response) > 0)
		{
			// Si la réponse semble correcte, on vérifie si l'API indique
			//	que la réponse est un succès et s'il existe les informations
			// 	attendues initialement.
			$response = $response[$identifier];

			if ($response["success"] === false)
			{
				// Si ce n'est pas le cas, on renvoie juste la valeur
				//	de secours.
				return $fallback;
			}

			// Dans le cas contraire, on retourne le nom du jeu comme attendu.
			return $response["data"]["name"];
		}

		// On retourne enfin le résultat par défaut si la demande a échouée.
		return $fallback;
	}

	//
	// Récupère l'identifiant unique du jeu actuellement monté sur le serveur de jeu
	//  du client à partir de son adresse IP.
	// 	Source : https://partner.steamgames.com/doc/webapi/ISteamApps#GetServersAtAddress
	//
	public function getGameIDByAddress(string $address, int $port): int
	{
		// On fait une requête à l'API centrale Steam pour récupérer
		//	les informations du serveur.
		$response = file_get_contents("https://api.steampowered.com/ISteamApps/GetServersAtAddress/v1/?addr=$address:$port");

		// On transforme par la suite ce résultat sous format JSON en
		//	tableau associatif pour la manipuler.
		$response = json_decode($response, true);

		if (is_array($response) && count($response) > 0)
		{
			// Si la réponse semble correcte, on vérifie si l'API indique
			//	que la réponse est un succès et s'il existe une liste
			//	d'informations comme attendu.
			$response = $response["response"];

			if ($response["success"] === false || count($response["servers"]) === 0)
			{
				// Si ce n'est pas le cas, on renvoie juste que le serveur
				//	utilise un jeu qui n'est pas sur la plate-forme Steam.
				return 0;
			}

			// Dans le cas contraire, on retourne l'identifiant du jeu comme attendu.
			return $response["servers"][0]["appid"];
		}

		// On retourne enfin un résultat par défaut si la requête a échouée
		//	quelque part (connexion Internet impossible, API hors service...).
		return 0;
	}
}