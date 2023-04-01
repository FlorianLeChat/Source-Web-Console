<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
// 	Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use App\Twig\AppRuntime;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
	// Permet de déclarer des fonctions Twig.
	public function getFunctions()
	{
		return [
			new TwigFunction("get_languages", [AppRuntime::class, "getLanguages"]),
		];
	}
}