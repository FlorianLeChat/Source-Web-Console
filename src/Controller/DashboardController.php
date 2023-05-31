<?php

//
// Contrôleur de la page du tableau de bord.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    public function index(): Response
    {
        return $this->render("dashboard.html.twig");
    }
	#[Route("/dashboard")]
	#[IsGranted("IS_AUTHENTICATED")]
}