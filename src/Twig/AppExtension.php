<?php

//
// Extension Twig pour les fonctions et filtres personnalisés.
//  Source : https://symfony.com/doc/current/templates.html#creating-lazy-loaded-twig-extensions
//
namespace App\Twig;

use App\Twig\AppRuntime;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
	// Déclaration des fonctions et filtres Twig personnalisés.
	public function getFunctions()
	{
		return [
			new TwigFunction("get_languages", [AppRuntime::class, "getLanguages"]),
			new TwigFunction("get_name_by_game_id", [AppRuntime::class, "getNameByGameID"]),
		];
	}
}