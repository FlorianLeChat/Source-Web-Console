<?php

//
// Contrôleur de la page des actions et des commandes.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ActionsController extends AbstractController
{
	#[Route("/actions")]
	public function index(): Response
	#[IsGranted("IS_AUTHENTICATED")]
	{

		// Si c'est le cas, on le redirige enfin vers la page des actions.
		return $this->render("actions.html.twig");
	}
}