<?php

//
// Contrôleur de la page du tableau de bord.
//
namespace App\Controller;

use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private Security $security,
		private ServerManager $manager,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
	)
	{
		$this->manager = $manager;
		$this->security = $security;
		$this->translator = $translator;
		$this->entityManager = $entityManager;
	}

	//
	// Route vers la page du tableau de bord.
	//
	#[Route("/dashboard")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_index");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("dashboard.html.twig", [

			// Récupération de l'historique des actions et commandes.
			"dashboard_logs" => [],

			// Liste des serveurs depuis la base de données.
			"dashboard_servers" => $this->entityManager->getRepository(Server::class)->findAll()

		]);
	}

	//
	// API vers le mécanisme de création de compte.
	//
	#[Route("/api/server/monitor", methods: ["GET"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function monitor(Request $request): Response|JsonResponse
	{
		// TODO : imposer un délai entre chaque requête pour éviter les abus.

		// On récupère d'abord le premier serveur lié au compte de l'utilisateur
		//  ou celui sélectionné par l'utilisateur.
		$session = $request->getSession();
		$repository = $this->entityManager->getRepository(Server::class);

		if ($cacheId = $session->get("serverId", 0))
		{
			// Serveur sélectionné par l'utilisateur.
			$server = $repository->findOneBy(["id" => $cacheId]);
		}
		else
		{
			// Serveur par défaut.
			$server = $repository->findOneBy([], ["id" => "ASC"]);
		}

		try
		{
			// On tente après d'établir une connexion avec le serveur.
			$this->manager->connect($server->getAddress(), $server->getPort(), $server->getPassword());

			// En cas de réussite, on récupère toutes les informations
			//	disponibles et fournies par le module d'administration.
			$details = $this->manager->query->GetInfo();

			// On encode alors certaines de ces informations pour les
			//	transmettre au client à travers le JavaScript.
			return new JsonResponse([

				// État du serveur.
				"state" => $this->translator->trans("dashboard.state." . ($details["Password"] ? "service" : "running"), ["%gamemode%" => $details["ModDesc"]]),

				// Carte/environnement.
				"map" => $details["Map"],

				// Nombre de joueurs humains et robots.
				"count" => $details["Players"] . "/" . $details["MaxPlayers"] . " [" . $details["Bots"] . "]",

				// Liste des joueurs
				"players" => $this->manager->query->GetPlayers()

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
			$this->manager->query->Disconnect();
		}
	}
}