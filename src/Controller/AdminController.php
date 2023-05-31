<?php

//
// Contrôleur de la page de l'administration.
//
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    public function index(): Response
    {
        return $this->render("admin.html.twig");
    }
	#[Route("/admin")]
	#[IsGranted(["IS_AUTHENTICATED_FULLY", "ROLE_ADMIN"])]
}