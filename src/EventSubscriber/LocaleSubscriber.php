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

	// Définition de la langue actuelle de la session.
	public function onKernelRequest(RequestEvent $event)
	{
		// On vérifie d'abord si la langue a déjà été définie par l'utilisateur.
		$request = $event->getRequest();
		$session = $request->getSession();

		if ($locale = $request->attributes->get("_locale"))
		{
			// Si c'est le cas, on enregistre celle-ci dans la session.
			$session->set("_locale", $locale);
		}
		else
		{
			// Dans le cas contraire, on tente alors d'utiliser la langue du navigateur
			//  ou la langue par défaut si les informations ne sont pas disponibles.
			$locale = $request->request->get("language", $request->server->get("HTTP_ACCEPT_LANGUAGE", self::DEFAULT_LOCALE));

			// On définit enfin la langue préférée de l'utilisateur dans la requête.
			$request->setLocale($session->get("_locale", substr($locale, 0, 2)));
		}
	}

	// Définition des écouteurs d'événements.
	public static function getSubscribedEvents()
	{
		return [
			// On définit la priorité à 20 pour que l'écouteur
			//  soit exécuté après ceux des autres contrôleurs.
			KernelEvents::REQUEST => [["onKernelRequest", 20]],
		];
	}
}