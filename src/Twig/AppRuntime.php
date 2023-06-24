<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
//  Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use App\Service\ServerManager;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Translation\DataCollectorTranslator as Translator;

class AppRuntime implements RuntimeExtensionInterface
{
	// Injection des dépendances.
	public function __construct(private Translator $translator, private ServerManager $serverManager) {}

	// Récupération des langues disponibles.
	public function getLanguages()
	{
		return $this->translator->getFallbackLocales();
	}

	// Récupération du nom d'un jeu par son identifiant unique.
	public function getNameByGameID(int $identifier, string $fallback = ""): string
	{
		return $this->serverManager->getNameByGameID($identifier, $fallback);
	}
}