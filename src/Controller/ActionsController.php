<?php

//
// Contrôleur de la page des actions et des commandes.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ActionsController extends AbstractController
{
	#[Route("/actions")]
	public function index(): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("app_index_index");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		return $this->render("actions.html.twig", [

			// État actuel de la restriction de la lampe torche.
			"actions_value_flashlight" => $rules["mp_flashlight"] ?? "",

			// État actuel de la restriction des logiciels de triche.
			"actions_value_cheats" => $rules["sv_cheats"] ?? "0",

			// État actuel de la restriction des communications vocales.
			"actions_value_voice" => $rules["sv_voiceenable"] ?? "0",

			// Liste des commandes personnalisées.
			// TODO : récupérer les commandes personnalisées.
			"actions_custom_commands" => []

		]);
	}
}