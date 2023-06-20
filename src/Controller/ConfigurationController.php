<?php

//
// Contrôleur de la page de la configuration.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConfigurationController extends AbstractController
{
	#[Route("/configuration")]
	#[IsGranted("IS_AUTHENTICATED")]
	public function index(): Response
	{
		// On inclut les paramètres du moteur TWIG pour la création de la page.
		return $this->render("configuration.html.twig", [

			// Identifiants du serveur de stockage.
			// TODO : remplacer par les identifiants du serveur de stockage.
			"configuration_credentials" => []

		]);
	}
}