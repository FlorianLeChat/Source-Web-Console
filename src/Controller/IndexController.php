<?php

//
// Contrôleur de la page d'accueil.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
	//
	// Route vers la page d'accueil.
	//
	#[Route("/", name: "index_page")]
	public function index(): Response
	{
		return $this->render("index.html.twig");
	}
}