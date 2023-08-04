<?php

//
// Contrôleur de la page des tâches planifiées.
//
namespace App\Controller;

use App\Entity\Task;
use App\Entity\Server;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class TasksController extends AbstractController
{
	//
	// Initialisation de certaines dépendances du contrôleur.
	//
	public function __construct(
		private readonly ServerManager $serverManager,
		private readonly ValidatorInterface $validator,
		private readonly TranslatorInterface $translator,
		private readonly EntityManagerInterface $entityManager,
	) {}

	//
	// Route vers la page des tâches planifiées.
	//
	#[Route("/tasks", name: "tasks_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		$servers = $this->entityManager->getRepository(Server::class)->findBy([
			"user" => $this->getUser()
		]);

		return $this->render("tasks.html.twig", [
			// Liste des tâches planifiées prévues.
			"tasks_list" => $this->entityManager->getRepository(Task::class)->findBy(
				["server" => $servers],
				["date" => "DESC"],
			10),

			// Liste des serveurs depuis la base de données.
			"tasks_servers" => $servers
		]);
	}

	//
	// API vers l'ajout d'une nouvelle tâche planifiée.
	//
	#[Route("/api/task/add", name: "tasks_add", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function add(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("server_tasks", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que le serveur existe bien et qu'il appartient à l'utilisateur.
		$user = $this->getUser();
		$serverId = intval($request->request->get("server", 0));
		$serverRepository = $this->entityManager->getRepository(Server::class);

		if (!$server = $serverRepository->findOneBy(["id" => $serverId, "user" => $user]))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie que l'utilisateur n'a pas déjà trop de tâches planifiées (non terminées).
		$tasksRepository = $this->entityManager->getRepository(Task::class);
		$servers = $serverRepository->findBy(["user" => $user]);

		if ($tasksRepository->count(["server" => $servers, "state" => Task::STATE_WAITING]) >= 10)
		{
			return new Response(
				$this->translator->trans("tasks.too_much"),
				Response::HTTP_TOO_MANY_REQUESTS
			);
		}

		// On vérifie ensuite que la date renseignée est valide.
		$now = new \DateTime("-1 day");
		$date = new \DateTime($request->request->get("date"));
		$future = new \DateTime("+1 year");

		if ($date < $now || $date > $future)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie après que les informations de la tâche planifiée sont valides.
		$task = new Task();
		$task->setServer($server);
		$task->setDate($date);
		$task->setAction($request->request->get("action"));
		$task->setState(Task::STATE_WAITING);

		if (count($this->validator->validate($task)) > 0)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On sauvegarde enfin la tâche planifiée dans la base de données
		//  et on retourne une réponse de succès.
		$tasksRepository->save($task, true);

		return new Response(
			$this->translator->trans("tasks.added"),
			Response::HTTP_CREATED
		);
	}

	//
	// API vers la suppression d'une tâche planifiée existante.
	//
	#[Route("/api/task/remove", name: "tasks_remove", methods: ["POST"])]
	#[IsGranted("IS_AUTHENTICATED")]
	public function remove(Request $request): Response
	{
		// On vérifie tout d'abord la validité du jeton CSRF.
		if (!$this->isCsrfTokenValid("server_tasks", $request->request->get("token")))
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On vérifie alors que la tâche ainsi que le serveur existent bien
		//  et qu'ils appartiennent à l'utilisateur.
		$taskId = intval($request->request->get("task", 0));
		$serverId = intval($request->request->get("server", 0));
		$repository = $this->entityManager->getRepository(Task::class);

		$task = $repository->findOneBy(["id" => $taskId, "server" => $serverId]);
		$server = $this->entityManager->getRepository(Server::class)->findOneBy([
			"id" => $serverId, "user" => $this->getUser()
		]);

		if (!$task || !$server)
		{
			return new Response(
				$this->translator->trans("form.server_check_failed"),
				Response::HTTP_BAD_REQUEST
			);
		}

		// On supprime enfin la tâche planifiée dans la base de données
		//  et on retourne une réponse de succès.
		$repository->remove($task, true);

		return new Response(
			$this->translator->trans("tasks.removed"),
			Response::HTTP_OK
		);
	}
}