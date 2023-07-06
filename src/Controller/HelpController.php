<?php

//
// Contrôleur de la page de l'assistance utilisateur.
//
namespace App\Controller;

use App\Entity\User;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HelpController extends AbstractController
{
	#[Route("/help", name: "help_page")]
	public function index(EntityManagerInterface $entityManager): Response
	{
		// On inclut les paramètres du moteur TWIG pour la création de la page.
		$repository = $entityManager->getRepository(User::class);

		return $this->render("help.html.twig", [

			// Nombre d'utilisateurs donateurs.
			"help_donators_count" => $repository->count(["roles" => "ROLE_DONOR"]),

			// Nombre de serveurs enregistrés.
			"help_servers_count" => $entityManager->getRepository(Server::class)->count([]),

			// Nombre de comptes utilisateurs.
			"help_users_count" => $repository->count([]),

			// Nombre de requêtes réalisées.
			// TODO : remplacer par le nombre de requêtes effectuées.
			"help_requests_count" => 0

		]);
	}
}