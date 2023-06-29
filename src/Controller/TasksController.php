<?php

//
// Contrôleur de la page des tâches planifiées.
//
namespace App\Controller;

use App\Entity\Task;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TasksController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private EntityManagerInterface $entityManager,
	) {}

	//
	// Route vers la page des tâches planifiées.
	//
	#[Route("/tasks", name: "app_tasks_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_page");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		/** @var User */
		$user = $this->getUser();
		$servers = $this->entityManager->getRepository(Server::class)->findBy(["client" => $user->getId()]);

		return $this->render("tasks.html.twig", [

			// Liste des tâches planifiées prévues.
			"tasks_list" => $this->entityManager->getRepository(Task::class)->findBy(["server" => $servers]),

			// Liste des serveurs depuis la base de données.
			"tasks_servers" => $servers,

		]);
	}
}