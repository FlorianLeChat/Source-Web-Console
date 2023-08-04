<?php

//
// Écouteur d'événements pour le limiteur de requêtes.
//
namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LimiterSubscriber implements EventSubscriberInterface
{
	//
	// Initialisation de certaines variables de l'écouteur.
	//
	public function __construct(
		private readonly RateLimiterFactory $apiLimiter,
		private readonly HttpClientInterface $client,
		private readonly TranslatorInterface $translator,
	) {}

	//
	// Définition de la langue actuelle de la session.
	//
	public function onKernelRequest(RequestEvent $event)
	{
		// On récupère tout d'abord la requête associée à l'événement.
		$request = $event->getRequest();

		// On vérifie ensuite si la requête est une requête API ou non
		//  avant de créer le limiteur de requêtes.
		$apiRoute = str_contains($request->getUri(), "/api/");
		$limiter = $this->apiLimiter->create($request->getUser());

		if ($apiRoute && !$limiter->consume(1)->isAccepted())
		{
			// Si l'utilisateur a dépassé la limite de requêtes autorisées,
			//  on lui renvoie une réponse d'erreur.
			$event->setResponse(new Response(
				$this->translator->trans("global.too_many"),
				Response::HTTP_TOO_MANY_REQUESTS
			));
		}
	}

	//
	// Déclaration des écouteurs d'événements.
	//
	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::REQUEST => ["onKernelRequest", 5]
		];
	}
}