<?php

//
// Écouteur d'événements pour les soumissions de formulaires.
//
namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class SubmitSubscriber implements EventSubscriberInterface
{
	// Clé privée de l'API Google reCAPTCHA.
	private string $recaptchaKey;

	//
	// Initialisation de certaines variables de l'écouteur.
	//
    public function __construct(
		private HttpClientInterface $client,
		private TranslatorInterface $translator,
		private ContainerBagInterface $parameters,
	) {
		$this->recaptchaKey = $this->parameters->get("app.recaptcha_private_key");
	}

	// Définition de la langue actuelle de la session.
	public function onKernelRequest(RequestEvent $event)
	{
		// On récupère tout d'abord la requête associée à l'événement.
		$request = $event->getRequest();

		if ($request->getMethod() !== "GET")
		{
			// Si la requête n'utilise pas la méthode « GET », on vérifie alors si
			//  le jeton reCAPTCHA est présent dans la requête.
			$token = $request->request->get("recaptcha");

			if (empty($token))
			{
				$event->setResponse(new Response(status: Response::HTTP_BAD_REQUEST));
			}

			// On envoie ensuite une requête à l'API Google reCAPTCHA pour vérifier
			//  si le jeton est valide ou non.
			$response = $this->client->request("POST", "https://www.google.com/recaptcha/api/siteverify?secret=" .  $this->recaptchaKey . "&response=$token");

			// On vérifie après si la requête a réussie ou non.
			if ($response->getStatusCode() !== 200)
			{
				$event->setResponse(new Response(
					$this->translator->trans("form.server_check_failed"),
					Response::HTTP_INTERNAL_SERVER_ERROR
				));
			}

			// Si elle a réussie, on vérifie le contenu de la réponse.
			$response = json_decode($response->getContent(), true);

			if (is_array($response) && ($response["success"] === false || $response["score"] < 0.7))
			{
				// On envoie une réponse d'erreur si le jeton est invalide
				//  ou si le score reCAPTCHA est trop faible.
				$event->setResponse(new Response(
					$this->translator->trans("form.server_check_failed"),
					Response::HTTP_FORBIDDEN
				));
			}
		}
	}

	// Définition des écouteurs d'événements.
	public static function getSubscribedEvents()
	{
		return [
			// On définit la priorité à 10 pour que l'écouteur
			//  soit exécuté après ceux des autres contrôleurs.
			KernelEvents::REQUEST => [["onKernelRequest", 10]],
		];
	}
}