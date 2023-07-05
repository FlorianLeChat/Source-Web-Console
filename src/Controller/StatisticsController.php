<?php

//
// Contrôleur de la page des statistiques.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StatisticsController extends AbstractController
{
	#[Route("/statistics", name: "statistics_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On affiche enfin la page des statistiques.
		return $this->render("statistics.html.twig", [

			// Données du nombre de joueurs connectés.
			"player_count_data" => [ 12, 4, 1, 0, 0, 0, 1, 3, 5, 1, 2, 4, 5, 6, 4, 15, 16, 18, 23, 45, 24, 26, 45, 34, 24 ],

			// Données d'utilisation du processeur du serveur (en %).
			"cpu_usage_data" => [ 12.4, 12.68, 25, 35, 24, 12, 10, 8, 6, 1, 36, 45, 75, 89, 100, 98, 64, 54, 62, 56, 68, 72, 41, 35, 43 ],

			// Données du taux de rafraîchissement du serveur (tickrate).
			"tick_rate_data" => [ 25, 26, 27, 28, 26, 25, 24, 23, 26, 27, 30, 23, 27, 28, 35, 41, 38, 36, 33, 31, 25, 36, 26, 27, 30 ]
		]);
	}
}