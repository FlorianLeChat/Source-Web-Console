<?php

//
// Contrôleur de la page des tâches planifiées.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TasksController extends AbstractController
{
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
		return $this->render("tasks.html.twig", [

			// Liste des tâches planifiées prévues.
			// TODO : remplacer par la liste des tâches planifiées prévues.
			"tasks_list" => [],

			// Liste des serveurs depuis la base de données.
			// TODO : remplacer par la liste des serveurs appartenant à l'utilisateur.
			"tasks_servers" => []

		]);
	}
}