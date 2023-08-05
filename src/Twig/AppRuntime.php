<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
//  Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use App\Service\ServerManager;
use Symfony\Component\Intl\Languages;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

final class AppRuntime implements RuntimeExtensionInterface
{
	// Injection des dépendances.
	public function __construct(
		private readonly Translator $translator,
		private readonly ServerManager $serverManager
	) {}

	// Récupération des langues disponibles.
	public function getLanguages()
	{
		return $this->translator->getFallbackLocales();
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