<?php

//
// Contrôleur de la page des mentions légales.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LegalController extends AbstractController
{
	#[Route("/legal")]
	public function index(): Response
	{
		return $this->render("legal.html.twig");
	}
}