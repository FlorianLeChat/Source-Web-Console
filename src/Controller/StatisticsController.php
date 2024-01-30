<?php

//
// Contrôleur de la page des statistiques.
//
namespace App\Controller;

use App\Entity\Stats;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StatisticsController extends AbstractController
{
	#[Route("/statistics", name: "statistics_page")]
	public function index(Request $request, EntityManagerInterface $entityManager): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On récupère ensuite les informations du serveur sélectionné précédemment
		//  par l'utilisateur.
		$server = $entityManager->getRepository(Server::class)->findOneBy([
			"id" => intval($request->getSession()->get("serverId", 0)), "user" => $this->getUser()
		]);

		// On affiche enfin la page des statistiques.
		$statistics = $server ? $entityManager->getRepository(Stats::class)->findBy(["server" => $server->getId()]) : [];

		return $this->render("statistics.html.twig", [
			// Heures de récupération des données.
			"time_data" => array_map(fn($value) => $value->getDate()->format(\DateTime::ATOM), $statistics),

			// Données du nombre de joueurs connectés.
			"player_count_data" => array_map(fn($value) => $value->getPlayerCount(), $statistics),

			// Données d'utilisation du processeur du serveur (en %).
			"cpu_usage_data" => array_map(fn($value) => $value->getCpuUsage(), $statistics),

			// Données du taux de rafraîchissement du serveur (tickrate).
			"tick_rate_data" => array_map(fn($value) => $value->getTickRate(), $statistics)
		]);
	}
}