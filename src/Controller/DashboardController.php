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
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(Request $request): Response
	{
		// On récupère d'abord le premier serveur lié au compte de l'utilisateur
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