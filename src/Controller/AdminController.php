<?php

//
// Contrôleur de la page de l'administration.
//
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
	#[Route("/admin", name: "admin_page")]
	#[IsGranted("ROLE_ADMIN")]
	public function index(EntityManagerInterface $entityManager): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On récupère ensuite la connexion à la base de données avant
		//  de récupérer toutes les tables de la base de données.
		$doctrine = $entityManager->getConnection();
		$result = $doctrine->executeQuery("SHOW TABLES;");

		// On inclut enfin les paramètres du moteur TWIG pour la
		//  création de la page.
		return $this->render("admin.html.twig",
			[
				// TODO : ajouter EasyAdmin pour la gestion des tables.

				// Génération de toutes les tables de la base de données.
				"admin_tables" => $result->fetchAllAssociative(),

				// Code HTML de l'affichage des tables.
				"admin_result" => ""
			]
		);
	}
}