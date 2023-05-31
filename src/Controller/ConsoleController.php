<?php

//
// Contrôleur de la page de la console interactive.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConsoleController extends AbstractController
{
	#[Route("/console")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(): Response
	{
		return $this->render("console.html.twig");
	}
}