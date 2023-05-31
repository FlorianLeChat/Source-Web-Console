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
    public function index(): Response
    {
        return $this->render("configuration.html.twig");
    }
	#[Route("/configuration")]
	#[IsGranted("IS_AUTHENTICATED")]
}