<?php

//
// Contrôleur de la page des actions et des commandes.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ActionsController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private ServerManager $serverManager,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager
	) {}

	//
	// Route vers la page des actions et des commandes.
	//
	#[Route("/actions", name: "app_actions_page")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_page");
		}

		// On récupère ensuite l'identifiant unique du serveur sélectionné
		//  précédemment par l'utilisateur.
		$serverId = intval($request->getSession()->get("serverId", 0));

		if ($serverId !== 0)
		{
			// Si un serveur est sélectionné, on récupère les informations
			//  le concernant.
			/** @var User */
			$user = $this->getUser();
			$server = $this->entityManager->getRepository(Server::class)->findOneBy(["id" => $serverId, "client" => $user->getId()]);

			try
			{
				// On tente après d'établir une connexion avec le serveur.
				$this->serverManager->connect($server->getAddress(), $server->getPort(), $server->getPassword());

				// En cas de réussite, on récupère toutes les informations
				//	disponibles et fournies par le module d'administration.
				$rules = $this->serverManager->query->GetRules();
			}
			catch (\Exception) {}
			finally
			{
				// Si tout se passe bien, on libère le socket réseau pour
				//	d'autres scripts du site.
				$this->serverManager->query->Disconnect();
			}
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("actions.html.twig", [

			// État actuel de la restriction de la lampe torche.
			"actions_value_flashlight" => $rules["mp_flashlight"] ?? "",

			// État actuel de la restriction des logiciels de triche.
			"actions_value_cheats" => $rules["sv_cheats"] ?? "0",

			// État actuel de la restriction des communications vocales.
			"actions_value_voice" => $rules["sv_voiceenable"] ?? "0",

			// Liste des commandes personnalisées.
			// TODO : récupérer les commandes personnalisées.
			"actions_custom_commands" => []

		]);
	}

	//
	// API vers l'exécution d'une action à distance sur le serveur.
	//
	#[Route("/api/server/action", name: "app_server_action", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function action(Request $request): Response
	{
		// TODO : imposer un délai entre chaque requête pour éviter les abus (https://symfony.com/doc/current/rate_limiter.html).

		// On vérifie tout d'abord la validité du jeton CSRF.
		$action = $request->request->get("action");

		if (!$this->isCsrfTokenValid("server_$action", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On récupère ensuite le serveur sélectionné par l'utilisateur.
		/** @var User */
		$user = $this->getUser();
		$value = $request->request->get("value");
		$serverId = intval($request->getSession()->get("serverId", 0));
		$repository = $this->entityManager->getRepository(Server::class);

		if ($serverId === 0 || !$action)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On récupère alors les données du serveur.
		$server = $repository->findOneBy(["id" => $serverId, "client" => $user->getId()]);

		try
		{
			// On tente après d'établir une connexion avec le serveur.
			$this->serverManager->connect($server->getAddress(), $server->getPort(), $server->getPassword());

			// On détermine l'action doit être réalisée sur le serveur.
			switch ($action)
			{
				case Server::ACTION_SHUTDOWN:
				{
					// Requête d'arrêt classique.
					$this->serverManager->query->Rcon("sv_shutdown");
					break;
				}

				case Server::ACTION_SHUTDOWN_FORCE:
				{
					// Requête d'arrêt forcée.
					$this->serverManager->query->Rcon("quit");
					break;
				}

				case Server::ACTION_RESTART:
				{
					// Requête de redémarrage.
					$this->serverManager->query->Rcon("_restart");
					break;
				}

				case Server::ACTION_UPDATE:
				{
					// Requête de mise à jour.
					$this->serverManager->query->Rcon("svc_update");
					break;
				}

				case Server::ACTION_SERVICE:
				{
					// Requête de mise en maintenance/verrouillage.
					$this->serverManager->query->Rcon(sprintf("sv_password \"%s\"", bin2hex(random_bytes(15))));
					break;
				}

				case "flashlight":
				{
					// Basculement de l'autorisation d'utilisation de la lampe torche.
					$this->serverManager->query->Rcon("toggle mp_flashlight");
					break;
				}

				case "cheats":
				{
					// Basculement de l'autorisation d'utilisation des commandes de triche.
					$this->serverManager->query->Rcon("toggle sv_cheats");
					break;
				}

				case "voice":
				{
					// Basculement de l'autorisation d'utilisation de la voix par IP.
					$this->serverManager->query->Rcon("toggle sv_voiceenable");
					break;
				}

				case "console":
				{
					// Exécution d'une commande sur le serveur.
					$this->serverManager->query->Rcon($value);
					break;
				}

				case "level":
				{
					// Changement de l'environnement/carte.
					$this->serverManager->query->Rcon("changelevel \"$value\"");
					break;
				}

				case "password":
				{
					// Changement du mot de passe du serveur.
					$this->serverManager->query->Rcon("sv_password \"$value\"");
					break;
				}

				case "gravity":
				{
					// Changement du niveau de gravité.
					$this->serverManager->query->Rcon("sv_gravity \"$value\"");
					break;
				}
			}

			// On envoie par la suite la réponse au client.
			return new Response(
				$this->translator->trans("global.action_success"),
				Response::HTTP_OK
			);
		}
		catch (\Exception $error)
		{
			// En cas d'erreur, on renvoie le message d'erreur à l'utilisateur.
			return new Response(
				$this->translator->trans("global.fatal_error", ["%error%" => $error->getMessage()]),
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}
		finally
		{
			// Si tout se passe bien, on libère enfin le socket réseau
			//	pour d'autres scripts du site.
			$this->serverManager->query->Disconnect();
		}
	}
}