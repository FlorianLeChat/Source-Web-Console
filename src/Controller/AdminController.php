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
	#[Route("/admin")]
	#[IsGranted("ROLE_ADMIN")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(EntityManagerInterface $entityManager): Response
	{
		// On récupère d'abord la connexion à la base de données avant
		//  de récupérer les tables de la base de données.
        $doctrine = $entityManager->getConnection();
        $result = $doctrine->executeQuery("SHOW TABLES;");

		// On inclut enfin les paramètres du moteur TWIG pour la
		//  création de la page.
		return $this->render("admin.html.twig", [

			// TODO : ajouter EasyAdmin pour la gestion des tables.

			// Génération de toutes les tables de la base de données.
			"admin_tables" => $result->fetchAllAssociative(),

			// Code HTML de l'affichage des tables.
			"admin_result" => ""

		]);
	}
}