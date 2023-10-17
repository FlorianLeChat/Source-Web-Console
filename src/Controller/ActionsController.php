<?php

//
// Contrôleur de la page des actions et des commandes.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Server;
use App\Entity\Command;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ActionsController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly ServerManager $serverManager,
		private readonly ValidatorInterface $validator,
		private readonly TranslatorInterface $translator,
		private readonly EntityManagerInterface $entityManager
	) {}

	//
	// Route vers la page des actions et des commandes.
	//
	#[Route("/actions", name: "actions_page")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On récupère ensuite l'identifiant unique du serveur sélectionné
		//  précédemment par l'utilisateur.
		$serverId = intval($request->getSession()->get("serverId", 0));

		if ($serverId !== 0)
		{
			try
			{
				// On tente après d'établir une connexion avec le serveur.
				$this->serverManager->connect($this->entityManager->getRepository(Server::class)->findOneBy([
					"id" => $serverId, "user" => $this->getUser()
				]));

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
			"actions_value_flashlight" => $rules["mp_flashlight"] ?? "0",

			// État actuel de la restriction des logiciels de triche.
			"actions_value_cheats" => $rules["sv_cheats"] ?? "0",

			// État actuel de la restriction des communications vocales.
			"actions_value_voice" => $rules["sv_voiceenable"] ?? "0",

			// Liste des commandes personnalisées.
			"actions_custom_commands" => $this->entityManager->getRepository(Command::class)->findAll()
		]);
	}

	//
	// API vers l'exécution d'une action à distance sur le serveur.
	//
	#[Route("/api/server/action", name: "server_action", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function action(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		$action = $request->request->get("action", "none");

		if (!$this->isCsrfTokenValid("server_$action", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.csrf_token"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On récupère ensuite le serveur sélectionné par l'utilisateur.
		$serverId = intval($request->getSession()->get("serverId", 0));

		if ($serverId === 0)
		{
			return new Response(
				$this->translator->trans("form.no_selected_server"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que le serveur existe bien et qu'il appartient à l'utilisateur.
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => $serverId, "user" => $this->getUser()
		]);

		if (!$server)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		try
		{
			// On tente après d'établir une connexion avec le serveur.
			$this->serverManager->connect($server);

			// On détermine l'action doit être réalisée sur le serveur.
			$value = $request->request->get("value", "none");

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

				default:
				{
					// Si aucune action n'est trouvée, alors il s'agit probablement
					//	d'une commande personnalisée. On tente dans ce cas de récupérer
					//	les données de toutes les commandes pour trouver celles qui
					//	correspondent à l'action.
					$command = $this->entityManager->getRepository(Command::class)->findOneBy([
						"id" => $action, "user" => $this->getUser()
					]);

					// On définit le nom de la commande si elle a été trouvée, sinon
					//  on indique qu'il s'agit d'une commande issue de la console interactive.
					$action = $command ?? "console";

					$this->serverManager->query->Rcon($command ? ($command->getContent() . " \"$value\"") : $value);

					break;
				}
			}

			// On enregistre l'action réalisée dans les événements journalisés.
			$event = new Event();
			$event->setServer($server);
			$event->setDate(new \DateTime());
			$event->setAction($action);

			$this->entityManager->getRepository(Event::class)->save($event, true);

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

	//
	// API vers l'ajout d'une nouvelle commande personnalisée.
	//
	#[Route("/api/command/add", name: "command_add", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function add(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("command_add", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.csrf_token"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie ensuite que l'utilisateur n'a pas déjà trop commandes
		//  personnalisées.
		$user = $this->getUser();
		$repository = $this->entityManager->getRepository(Command::class);

		if ($repository->count(["user" => $user]) >= ($this->isGranted("ROLE_DONOR") ? 2 : 1))
		{
			return new Response(
				$this->translator->trans("actions.too_much"),
				Response::HTTP_TOO_MANY_REQUESTS
			);
		}

		// On vérifie après que les informations de la commande personnalisée
		//  sont valides.
		/** @var User $user */
		$command = new Command();
		$command->setUser($user);
		$command->setTitle($request->request->get("title"));
		$command->setContent($request->request->get("content"));

		if (count($violations = $this->validator->validate($command)) > 0)
		{
			return new Response(
				$violations->get(0)->getMessage(),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On sauvegarde enfin la commande personnalisée dans la
		//  base de données et on retourne une réponse de succès.
		$repository->save($command, true);

		return new Response(
			$this->translator->trans("actions.added"),
			Response::HTTP_CREATED
		);
	}

	//
	// API vers la suppression d'une commande personnalisée.
	//
	#[Route("/api/command/remove", name: "command_remove", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("command_remove", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.csrf_token"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que la commande existe bien et qu'elle appartient
		//  bien à l'utilisateur.
		$repository = $this->entityManager->getRepository(Command::class);
		$command = $repository->findOneBy([
			"id" => intval($request->request->get("id", 0)), "user" => $this->getUser()
		]);

		if (!$command)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On supprime enfin la commande personnalisée dans la base de données
		//  et on retourne une réponse de succès.
		$repository->remove($command, true);

		return new Response(
			$this->translator->trans("actions.removed"),
			Response::HTTP_OK
		);
	}
}