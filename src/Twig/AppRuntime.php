<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
//  Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Translation\DataCollectorTranslator as Translator;

class AppRuntime implements RuntimeExtensionInterface
{
	private $languages;

	// Injection des dépendances.
	public function __construct(private Translator $translator)
	{
		$this->languages = $translator->getFallbackLocales();
	}

	// Récupération des langues disponibles.
	public function getLanguages()
	{
		return $this->languages;
	}

	// Récupération du nom d'un jeu par son identifiant unique.
	public function getNameByGameID()
	{
		// TODO : remettre en place les anciennes fonctions.
		return 4000;
	}
}