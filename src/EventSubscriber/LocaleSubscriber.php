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
	// Définition de la langue par défaut.
	private const DEFAULT_LOCALE = "en";

	//
	// Définition de la langue actuelle de la session.
	//
	public function onKernelRequest(RequestEvent $event)
	{
		// On récupère d'abord les données de la requête et de la session.
		$request = $event->getRequest();
		$session = $request->getSession();

		// On vérifie ensuite si une langue est définie par l'utilisateur
		//  au travers d'un formulaire ou d'un paramètre d'URL.
		if ($locale = $request->request->get("_locale", $request->query->get("_locale")))
		{
			// Si c'est le cas, on l'enregistre alors dans la session.
			$session->set("_locale", $locale);
		}
		else if (!$session->get("_locale"))
		{
			// Dans le cas contraire et si aucune langue existe dans la session,
			//  on récupère la langue du navigateur de l'utilisateur ou on utilise
			//  la langue par défaut (arbitraire).
			$session->set("_locale", substr($request->server->get("HTTP_ACCEPT_LANGUAGE", self::DEFAULT_LOCALE), 0, 2));
		}

		// On définit enfin la langue de la requête.
		$request->setLocale($session->get("_locale"));
	}

	//
	// Déclaration des écouteurs d'événements.
	//
	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::REQUEST => ["onKernelRequest", 20]
		];
	}
}