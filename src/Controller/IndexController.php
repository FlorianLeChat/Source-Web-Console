<?php

//
// Contrôleur de la page d'accueil.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
	#[Route("/", name: "index_page")]
	public function index(): Response
	{
		return $this->render("index.html.twig", [
			// État d'activation des services Google reCAPTCHA.
			"index_recaptcha_enabled" => $this->getParameter("app.recaptcha_enabled") === "true"
		]);
	}
}