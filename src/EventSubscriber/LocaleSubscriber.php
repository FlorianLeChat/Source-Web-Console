<?php

//
// Écouteur d'événements pour la langue.
//  Source : https://symfony.com/doc/current/session.html#creating-a-localesubscriber
//
namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LocaleSubscriber implements EventSubscriberInterface
{
	// Définition de la langue par défaut.
	private const DEFAULT_LOCALE = "en";

	//
	// Définition de la langue actuelle de la session.
	//
	public function onKernelRequest(RequestEvent $event)
	{
		// On détermine d'abord la langue de la requête et du navigateur.
		$request = $event->getRequest();
		$requestLocale = $request->request->get("_locale", $request->query->get("_locale"));
		$browserLocale = substr($request->server->get("HTTP_ACCEPT_LANGUAGE", self::DEFAULT_LOCALE), 0, 2);

		// On vérifie ensuite si la route ne nécessite pas l'utilisation du
		//  mécanisme de session.
		if ($request->attributes->get("_stateless"))
		{
			// Si c'est le cas, on définit la langue de la requête en fonction
			//  des informations récupérées précédemment.
			$request->setLocale($requestLocale ?? $browserLocale);
			return;
		}

		// On vérifie après si la langue de la requête est définie.
		$session = $request->getSession();

		if ($requestLocale)
		{
			// Si c'est le cas, on l'enregistre alors dans la session.
			$session->set("_locale", $requestLocale);
		}
		else if (!$session->get("_locale"))
		{
			// Dans le cas contraire et si aucune langue existe dans la session,
			//  on récupère la langue du navigateur de l'utilisateur.
			$session->set("_locale", $browserLocale);
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