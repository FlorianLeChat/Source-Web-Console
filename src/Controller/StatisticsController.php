<?php

//
// Contrôleur de la page des statistiques.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StatisticsController extends AbstractController
{
	#[Route("/statistics")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(): Response
	{
		return $this->render("statistics.html.twig");
	}
}