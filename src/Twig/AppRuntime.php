<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
//  Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use App\Service\ServerManager;
use Symfony\Component\Intl\Languages;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

final class AppRuntime implements RuntimeExtensionInterface
{
	// Durée du cache pour les méta-données du site.
	private const CACHE_LIFETIME = 3600 * 24 * 7;

	// Injection des dépendances.
	public function __construct(
		private readonly TranslatorInterface $translator,
		private readonly HttpClientInterface $client,
		private readonly CacheInterface $cache,
		private readonly ServerManager $serverManager,
	) {}

	// Récupération des matadonnées du site.
	public function getMetadata()
	{
		// On vérifie d'abord si les méta-données sont déjà en cache.
		return $this->cache->get("swc_metadata", function (ItemInterface $item): array
		{
			// On indique ensuite une durée de vie de persistance
			//  temporaire pour le cache en cas d'erreur.
			$item->expiresAfter(1);

			// On fait plusieurs requêtes à l'API GitHub pour récupérer
			//  les informations du dépôt, de l'auteur et des changements.
			$repository = $this->client->request(
				"GET", "https://api.github.com/repos/FlorianLeChat/Source-Web-Console"
			);

			$author = $this->client->request(
				"GET", "https://api.github.com/users/FlorianLeChat"
			);

			$commits = $this->client->request(
				"GET", "https://api.github.com/repos/FlorianLeChat/Source-Web-Console/commits/master"
			);

			// On vérifie après si les requêtes ont bien abouties.
			if ($repository->getStatusCode() !== 200 || $author->getStatusCode() !== 200 || $commits->getStatusCode() !== 200)
			{
				// Si ce n'est pas le cas, on renvoie juste un tableau vide
				//  pour éviter de provoquer des erreurs.
				return [];
			}

			// On récupère les résultats par la suite sous forme de tableau
			//  associatif pour pouvoir les manipuler plus facilement.
			$repository = $repository->toArray();
			$author = $author->toArray();
			$commits = $commits->toArray();

			if (count($repository) > 0 && count($author) > 0 && count($commits) > 0)
			{
				// On définit alors la durée de vie de persistance définitive
				//  pour le cache si les requêtes ont abouties.
				$item->expiresAfter(self::CACHE_LIFETIME);

				// On retourne dans ce cas un tableau contenant les méta-données
				//  générales du site.
				return [
					"url" => $repository["homepage"],
					"title" => $repository["name"],
					"author" => [
						"name" => $author["name"],
						"url" => $author["html_url"]
					],
					"banner" => sprintf("https://opengraph.githubassets.com/%s/%s",
						$commits["sha"], $repository["full_name"]
					),
					"source" => $repository["html_url"],
					"twitter" => "@" . $author["twitter_username"],
					"keywords" => implode(",", $repository["topics"]),
					"description" => $repository["description"]
				];
			}

			// On retourne enfin le résultat par défaut si les requêtes
			//  n'ont pas abouties ou si les données ne sont pas valides.
			return [];
		});
	}

	// Récupération des langues disponibles.
	public function getLanguages()
	{
		if ($this->translator instanceof Translator || method_exists($this->translator, "getFallbackLocales"))
		{
			return $this->translator->getFallbackLocales();
		}

		return [];
	}

	// Récupération du nom d'une langue par son code ISO 639-1.
	public function getLanguageName(string $locale): string
	{
		return Languages::getName($locale);
	}

	// Récupération du nom d'un jeu par son identifiant unique.
	public function getNameByGameID(int $identifier, string $fallback = ""): string
	{
		return $this->serverManager->getNameByGameID($identifier, $fallback);
	}
}