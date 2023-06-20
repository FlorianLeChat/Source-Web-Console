<?php

//
// Contrôleur de la page des tâches planifiées.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TasksController extends AbstractController
{
	#[Route("/tasks")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(): Response
	{
		// On inclut les paramètres du moteur TWIG pour la création de la page.
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