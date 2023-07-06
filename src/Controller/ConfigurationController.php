<?php

//
// Contrôleur de la page de la configuration.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConfigurationController extends AbstractController
{
	#[Route("/configuration", name: "configuration_page")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("configuration.html.twig",
			[
				// Identifiants du serveur de stockage.
				// TODO : remplacer par les identifiants du serveur de stockage.
				"configuration_credentials" => []
			]
		);
	}
}