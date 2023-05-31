<?php

//
// Contrôleur de la page de l'assistance utilisateur.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HelpController extends AbstractController
{
    public function index(): Response
    {
        return $this->render("help.html.twig");
    }
	#[Route("/help")]
}