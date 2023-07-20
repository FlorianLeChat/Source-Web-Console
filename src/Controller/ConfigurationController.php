<?php

//
// Contrôleur de la page de configuration des informations de stockage.
//
namespace App\Controller;

use App\Entity\Storage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConfigurationController extends AbstractController
{

	//
	// Route vers la page de configuration des informations de stockage.
	//
	#[Route("/configuration", name: "configuration_page")]
	public function index(Request $request, EntityManagerInterface $entityManager): Response
	{
		// On vérifie d'abord que l'utilisateur est bien connecté avant d'accéder
		//  à la page, sinon on le redirige vers la page d'accueil.
		if (!$this->isGranted("IS_AUTHENTICATED"))
		{
			return $this->redirectToRoute("index_page");
		}

		// On inclut enfin les paramètres du moteur TWIG pour la création de la page.
		$repository = $entityManager->getRepository(Storage::class);

		return $this->render("configuration.html.twig", [
			// Identifiants du serveur de stockage.
			"configuration_credentials" => $repository->findBy([
				"server" => intval($request->getSession()->get("serverId", 0))
			])
		]);
	}
}