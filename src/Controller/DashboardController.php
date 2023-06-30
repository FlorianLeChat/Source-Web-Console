<?php

//
// Contrôleur de la page du tableau de bord.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private ServerManager $serverManager,
		private ValidatorInterface $validator,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
	) {}

	//
	// Route vers la page du tableau de bord.
	//
	#[Route("/dashboard", name: "app_dashboard_page")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_page");
		}

		// On récupère ensuite l'identifiant unique du serveur sélectionné par
		//  l'action de l'utilisateur.
		/** @var User */
		$user = $this->getUser();
		$cacheId = intval($request->getSession()->get("serverId", 0));
		$serverId = intval($request->request->get("id", 0));
		$repository = $this->entityManager->getRepository(Server::class);

		if ($serverId !== 0 && $request->getMethod() === "POST")
		{
			// On vérifie également la validité du jeton CSRF.
			$action = $request->request->get("action", "none");

			if (!$this->isCsrfTokenValid("server_$action", $request->request->get("token")))
			{
				return new Response(status: Response::HTTP_BAD_REQUEST);
			}

			// On détermine après l'action doit être réalisée sur le serveur.
			switch ($action)
			{
				// Connexion à un serveur.
				case "connect":
				{
					// On vérifie que l'utilisateur a bien sélectionné un serveur.
					if ($serverId !== 0)
					{
						// Si c'est le cas, on enregistre l'identifiant du serveur dans la
						//  session de l'utilisateur.
						$request->getSession()->set("serverId", $serverId);
					}

					break;
				}

				// Édition d'un serveur.
				case "edit":
				{
					// Vérification de l'existence et de l'appartenance du serveur à l'utilisateur.
					if (!$server = $repository->findOneBy(["id" => $serverId, "client" => $user->getId()]))
					{
						return new Response(status: Response::HTTP_UNAUTHORIZED);
					}

					// Enregistrement des modifications du serveur.
					$server->setAddress($address = $request->request->get("address", $server->getAddress()));
					$server->setPort($port = $request->request->get("port", $server->getPort()));
					$server->setGame($this->serverManager->getGameIDByAddress($address, $port));

					// Chiffrement du nouveau mot de passe administrateur.
					if (!empty($password = $request->request->get("password")))
					{
						$server->setPassword($this->serverManager->encryptPassword($password));
					}

					// Vérification de la validité des nouvelles informations.
					if (count($this->validator->validate($server)) > 0)
					{
						return new Response(status: Response::HTTP_BAD_REQUEST);
					}

					// Sauvegarde dans la base de données.
					$repository->save($server, true);

					break;
				}

				// Suppression définitive d'un serveur.
				case "delete":
				{
					// Vérification de l'existence et de l'appartenance du serveur à l'utilisateur.
					if (!$server = $repository->findOneBy(["id" => $serverId, "client" => $user->getId()]))
					{
						return new Response(status: Response::HTTP_UNAUTHORIZED);
					}

					// Suppression dans la base de données.
					$repository->remove($server, true);

					break;
				}
			}
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("dashboard.html.twig", [

			// Récupération de l'historique des actions et commandes.
			"dashboard_logs" => $this->entityManager->getRepository(Event::class)->findBy(["server" => $cacheId], ["id" => "DESC"], 3),

			// Liste des serveurs depuis la base de données.
			"dashboard_servers" => $repository->findBy(["client" => $user->getId()])

		]);
	}

	//
	// API vers la surveillance des constantes du serveur.
	//
	#[Route("/api/server/monitor", name: "app_server_monitor", methods: ["GET"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function monitor(Request $request): Response|JsonResponse
	{
		// TODO : imposer un délai entre chaque requête pour éviter les abus (https://symfony.com/doc/current/rate_limiter.html).

		// On récupère d'abord le premier serveur lié au compte de l'utilisateur
		//  ou celui sélectionné par l'utilisateur.
		/** @var User */
		$user = $this->getUser();
		$serverId = intval($request->getSession()->get("serverId", 0));
		$repository = $this->entityManager->getRepository(Server::class);

		if ($serverId !== 0)
		{
			// Serveur sélectionné par l'utilisateur.
			$server = $repository->findOneBy(["id" => $serverId, "client" => $user->getId()]);
		}
		else
		{
			// Serveur par défaut.
			$server = $repository->findOneBy(["client" => $user->getId()], ["id" => "ASC"]);

			// Enregistrement automatique.
			$request->getSession()->set("serverId", $server->getId());
		}

		try
		{
			// On tente après d'établir une connexion avec le serveur.
			$this->serverManager->connect($server->getAddress(), $server->getPort(), $server->getPassword());

			// En cas de réussite, on récupère toutes les informations
			//	disponibles et fournies par le module d'administration.
			$details = $this->serverManager->query->GetInfo();

			// On encode alors certaines de ces informations pour les
			//	transmettre au client à travers le JavaScript.
			return new JsonResponse([

				// État du serveur.
				"state" => $this->translator->trans(sprintf("dashboard.state.%s", $details["Password"] ? "service" : "running"), ["%gamemode%" => $details["ModDesc"]]),

				// Carte/environnement.
				"map" => $details["Map"],

				// Nombre de joueurs humains et robots.
				"count" => sprintf("%d/%d [%d]", $details["Players"], $details["MaxPlayers"], $details["Bots"]),

				// Liste des joueurs
				"players" => $this->serverManager->query->GetPlayers()

			], JsonResponse::HTTP_OK);
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