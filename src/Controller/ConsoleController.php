<?php

//
// Contrôleur de la page de la console interactive.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConsoleController extends AbstractController
{
	#[Route("/console", name: "console_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On affiche enfin la page de la console interactive.
		return $this->render("console.html.twig");
	}
}