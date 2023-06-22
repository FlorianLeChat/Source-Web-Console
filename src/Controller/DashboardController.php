<?php

//
// Contrôleur de la page du tableau de bord.
//
namespace App\Controller;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private Security $security,
		private TranslatorInterface $translator,
		private EntityManagerInterface $entityManager,
	)
	{
		$this->security = $security;
		$this->translator = $translator;
		$this->entityManager = $entityManager;
	}

	//
	// Route vers la page du tableau de bord.
	//
	#[Route("/dashboard")]
	public function index(Request $request): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_index");
		}

		// On récupère ensuite le premier serveur lié au compte de l'utilisateur
		//  ou celui sélectionné par l'utilisateur.
		$session = $request->getSession();
		$serverRepository = $this->entityManager->getRepository(Server::class);

		if ($cacheId = $session->get("serverId", 0))
		{
			$serverData = $serverRepository->findOneBy(["id" => $cacheId]);
		}
		else
		{
			$serverData = $serverRepository->findOneBy([], ["id" => "ASC"]);
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("dashboard.html.twig", [

			// Récupération de l'historique des actions et commandes.
			"dashboard_logs" => [],

			// Liste des serveurs depuis la base de données.
			"dashboard_servers" => $serverRepository->findAll()

		]);
	}
}