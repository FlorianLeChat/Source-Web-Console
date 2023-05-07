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

	// Permet d'injecter des dépendances dans les fonctions Twig.
	public function __construct(private Translator $translator)
	{
        $this->languages = $translator->getFallbackLocales();
	}

	// Permet de récupérer la liste des langues disponibles.
	public function getLanguages()
	{
		return $this->languages;
	}
}