<?php

//
// Écouteur d'événements pour la langue.
//  Source : https://symfony.com/doc/current/session.html#creating-a-localesubscriber
//
namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
	// Permet de définir la langue par défaut.
	public function __construct(private string $defaultLocale = "en") {}

	// Permet de définir la langue en fonction de la session.
	public function onKernelRequest(RequestEvent $event)
	{
		// On vérifie d'abord si la langue a déjà été définie par l'utilisateur.
		$request = $event->getRequest();

		if ($locale = $request->attributes->get("_locale"))
		{
			// Si c'est le cas, on enregistre la nouvelle langue de la session.
			$request->getSession()->set("_locale", $locale);
		}
		else
		{
			// Dans le cas contraire, on tente alors d'utiliser la langue du navigateur
			//  ou la langue par défaut si les informations ne sont pas disponibles.
			$locale = substr($request->get("language", $request->server->get("HTTP_ACCEPT_LANGUAGE", $this->defaultLocale)), 0, 2);

			// On définit enfin la langue actuelle de la session.
			$request->setLocale($request->getSession()->get("_locale", $locale));
		}
	}

	// Permet de définir les événements à écouter.
	public static function getSubscribedEvents()
	{
		return [
			// On définit la priorité à 20 pour que l'écouteur
			//  soit exécuté après celui de la classe IndexController.
			KernelEvents::REQUEST => [["onKernelRequest", 20]],
		];
	}
}